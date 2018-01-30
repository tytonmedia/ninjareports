<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Schedule;
use Session;
use \FacebookAds\Http\Exception\AuthorizationException;
use \FacebookAds\Object\AdAccount;
use \FacebookAds\Object\Fields\AdsInsightsFields;
use \FacebookAds\Object\Fields\CampaignFields;
use \FacebookAds\Object\Values\AdsInsightsBreakdownsValues;
use \FacebookAds\Object\Values\AdsInsightsDatePresetValues;

class CronController extends Controller
{

    public function run()
    {
        $next_send_time = date('Y-m-d H:i:00');
        $reports = Report::where('next_send_time', $next_send_time)->with('account', 'ad_account', 'property', 'profile')->get();
        if ($reports && count($reports) > 0) {
            foreach ($reports as $report) {
                $recipients = explode(',', $report->recipients);
                if (is_array($recipients) && count($recipients) > 0) {
                    foreach ($recipients as $email) {
                        Schedule::create([
                            'user_id' => $report->user_id,
                            'report_id' => $report->id,
                            'recipient' => $email,
                        ]);
                    }
                }
            }
        }
    }
    public function report($id)
    {
        $report = Report::where('id', $id)
            ->with('account', 'ad_account', 'property', 'profile')
            ->first();
        if ($report) {
            if ($report->account->type == 'facebook') {

                $params = [];
                switch ($report->frequency) {
                    case "weekly":
                        $params['date_preset'] = AdsInsightsDatePresetValues::LAST_7D;
                        break;
                    case "monthly":
                        $params['date_preset'] = AdsInsightsDatePresetValues::LAST_30D;
                        break;
                    case "yearly":
                        $endDate = date('Y-m-d', strtotime(date('Y') . '-' . $report->ends_at));
                        $startDate = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
                        $params['time_range'] = ("{'since': '" . $startDate . "', 'until': '" . $endDate . "'}");
                        break;
                    default:
                        $params['date_preset'] = AdsInsightsDatePresetValues::TODAY;
                }
                $fb = fb_connect();
                \FacebookAds\Api::init(env('FACEBOOK_APP_ID'), env('FACEBOOK_SECRET'), fb_token());
                try {
                    $fb_ad_account = new AdAccount($report->ad_account->ad_account_id);
                } catch (\InvalidArgumentException $e) {
                    Session::flash('alert-danger', 'Invalid account.');
                    return redirect()->route('reports.index');
                }

                $fields = [AdsInsightsFields::CLICKS, AdsInsightsFields::IMPRESSIONS, AdsInsightsFields::CTR, AdsInsightsFields::CPM, AdsInsightsFields::CPC, AdsInsightsFields::SPEND];
                try {
                    // Get Campaigns
                    $campaigns = $fb_ad_account->getCampaigns([CampaignFields::ID, CampaignFields::NAME], ['limit' => 5]);
                    $campaigns_insights = [];
                    if ($campaigns && count($campaigns) > 0) {
                        foreach ($campaigns as $campaign) {
                            $cinsights = $campaign->getInsights($fields, $params);
                            foreach ($cinsights as $insight) {
                                $campaigns_insights[] = array_merge([
                                    'clicks' => $insight->clicks,
                                    'impressions' => $insight->impressions,
                                    'ctr' => $insight->ctr,
                                    'cpc' => $insight->cpc,
                                    'cpm' => $insight->cpm,
                                    'spend' => $insight->spend,
                                    'date_start' => $insight->date_start,
                                    'date_stop' => $insight->date_stop,
                                ], [
                                    'campaign' => $campaign->{CampaignFields::NAME},
                                ]);
                            }

                        }
                    }
                    pr($campaigns_insights);
                    // Get Total Insignhts
                    $params['breakdowns'] = [AdsInsightsBreakdownsValues::AGE, AdsInsightsBreakdownsValues::GENDER];
                    $insights = $fb_ad_account->getInsights($fields, $params);
                    if ($insights && count($insights) > 0) {
                        foreach ($insights as $insight) {
                            pr([
                                'clicks' => $insight->clicks,
                                'impressions' => $insight->impressions,
                                'ctr' => $insight->ctr,
                                'cpc' => $insight->cpc,
                                'cpm' => $insight->cpm,
                                'spend' => $insight->spend,
                                'date_start' => $insight->date_start,
                                'date_stop' => $insight->date_stop,
                                'age' => $insight->age,
                                'gender' => $insight->gender,
                            ]);
                        }
                        exit;
                    } else {
                        Session::flash('alert-danger', 'No data available. Please try again later.');
                        return redirect()->route('reports.index');
                    }
                } catch (AuthorizationException $e) {
                    Session::flash('alert-danger', $e->getMessage());
                    return redirect()->route('reports.index');
                }
            }
            if ($report->account->type == 'analytics') {
                $client = analytics_connect();
                $client->setAccessToken(analytics_token());
                $analytics = new \Google_Service_Analytics($client);
                $params = array(
                    'dimensions' => 'ga:source,ga:operatingSystem,ga:country',
                    //'sort' => '-ga:sessions,ga:country',
                    //'max-results' => '5'
                );
                $results = $analytics->data_ga->get(
                    'ga:' . $report->profile->view_id, 'today', 'today', 'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:bounceRate,ga:newUsers,ga:sessionsPerUser', $params);
                $insights = $results->totalsForAllResults;
                $metrics = $results->rows;
                if ($metrics && count($metrics) > 0) {
                    $final_metrics_array = [];
                    $sources_result = [];
                    $operating_system_result = [];
                    $locations_result = [];
                    foreach ($metrics as $metric) {
                        $sources_result[] = $metric[0];
                        $operating_system_result[] = $metric[1];
                        $locations_result[] = $metric[2];
                    }
                    $sources = array_unique($sources_result);
                    $operating_systems = array_unique($operating_system_result);
                    $locations = array_unique($locations_result);
                    $source_keys = [];
                    if ($sources && count($sources) > 0) {
                        foreach ($sources as $source) {
                            //pr(array_keys($metrics, $source));
                        }
                    }
                    pr($sources);
                    pr($operating_systems);
                    pr($locations);
                }
                exit;
            }
            if ($report->account->type == 'adword') {
                $session = adwords_session($report->ad_account->ad_account_id);
                $reportQuery = 'SELECT CampaignId, AdGroupId, Id, Criteria, CriteriaType, '
                    . 'Impressions, Clicks, Cost FROM CRITERIA_PERFORMANCE_REPORT '
                    . 'WHERE Status IN [ENABLED, PAUSED] DURING LAST_7_DAYS';
                $reportDownloader = new \Google\AdsApi\AdWords\Reporting\v201710\ReportDownloader($session);
                $reportSettingsOverride = (new \Google\AdsApi\AdWords\ReportSettingsBuilder())
                    ->includeZeroImpressions(false)
                    ->build();
                $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
                    $reportQuery, \Google\AdsApi\AdWords\Reporting\v201710\DownloadFormat::XML, $reportSettingsOverride);
                pr($reportDownloadResult);
                exit;
            }
        } else {
            Session::flash('alert-danger', 'Report not found.');
            return redirect()->route('reports.index');
        }
    }

}
