<?php

namespace App\Http\Controllers;

use App\Models\Plan;
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
        $reports = Report::where('next_send_time', $next_send_time)->where('is_active', 1)->with('user', 'account', 'ad_account', 'property', 'profile')->get();
        if ($reports && count($reports) > 0) {
            foreach ($reports as $report) {
                $current_plan = $report->user->current_billing_plan ? $report->user->current_billing_plan : 'free_trial';
                $plan = Plan::whereTitle($current_plan)->first();
                $reports_sent_count = Schedule::whereUserId($report->user->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
                if ($reports_sent_count <= $plan->reports) {
                    $recipients = explode(',', $report->recipients);
                    if (is_array($recipients) && count($recipients) > 0) {
                        $this->report($report, $recipients);
                        update_schedule($report, $report->user_id);
                    }
                }
            }
        }
    }
    public function report($report, $recipients)
    {
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
                \FacebookAds\Api::init(env('FACEBOOK_APP_ID'), env('FACEBOOK_SECRET'), fb_token($report->user_id));
                try {
                    $fb_ad_account = new AdAccount($report->ad_account->ad_account_id);
                } catch (\InvalidArgumentException $e) {
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
                    $top_ad_campaigns = '';
                    if (count($campaigns_insights) > 0) {
                        foreach ($campaigns_insights as $campaign_insight) {
                            $campaign_insight = (object) $campaign_insight;
                            $campaign_insight_cpm = number_format((float) $campaign_insight->cpm, 3, '.', '');
                            $top_ad_campaigns .= '<tr style="border-bottom:1px solid #ccc"><td style="padding:5px;">' . $campaign_insight->campaign . '</td><td style="padding:5px;">' . $campaign_insight->impressions . '</td><td style="padding:5px;">' . $campaign_insight->clicks . '</td><td style="padding:5px;">' . $campaign_insight_cpm . '%</td><td style="padding:5px;">' . $campaign_insight->ctr . '</td><td style="padding:5px;">' . $campaign_insight->cpc . '</td><td style="padding:5px;">$' . $campaign_insight->spend . '</td></tr>';
                        }
                    } else {
                        $top_ad_campaigns = 'No data';
                    }
                    $total_clicks = 'No data';
                    $total_impressions = 'No data';
                    $total_ctr = 'No data';
                    $total_cpm = 'No data';
                    $total_cpc = 'No data';
                    $total_spend = 'No data';
                    $fb_ads_data = [];
                    $ages_graph_url = 'no_data';
                    $genders_graph_url = 'no_data';

                    // Get Total Insignhts
                    $params['breakdowns'] = [AdsInsightsBreakdownsValues::AGE, AdsInsightsBreakdownsValues::GENDER];
                    $insights = $fb_ad_account->getInsights($fields, $params);
                    if ($insights && count($insights) > 0) {
                        $total_clicks = 0;
                        $total_impressions = 0;
                        $total_ctr = 0;
                        $total_cpm = 0;
                        $total_cpc = 0;
                        $total_spend = 0;
                        foreach ($insights as $insight) {
                            $total_clicks += $insight->clicks;
                            $total_impressions += $insight->impressions;
                            $total_ctr += number_format((float) $insight->ctr, 3, '.', '');
                            $total_cpm += number_format((float) $insight->cpm, 3, '.', '');
                            $total_cpc += number_format((float) $insight->cpc, 3, '.', '');
                            $total_spend += number_format((float) $insight->spend, 2, '.', '');
                            $fb_ads_data[] = [
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
                            ];
                        }
                    }
                    $fb_ages = [];
                    $fb_genders = [];
                    if (count($fb_ads_data) > 0) {
                        foreach ($fb_ads_data as $fb_ad_data) {
                            $fb_ages[] = $fb_ad_data['age'];
                            $fb_genders[] = $fb_ad_data['gender'];
                        }

                        $ages = [];
                        $genders = [];
                        if (count($fb_ages) > 0) {
                            $ages = array_unique($fb_ages);
                        }
                        if (count($fb_genders) > 0) {
                            $genders = array_unique($fb_genders);
                        }

                        $ages_keys = [];
                        if (count($ages) > 0) {
                            foreach ($ages as $age) {
                                $ages_keys[$age] = $this->getKeys($fb_ads_data, 'age', $age);
                            }
                        }

                        $genders_keys = [];
                        if (count($genders) > 0) {
                            foreach ($genders as $gender) {
                                $genders_keys[$gender] = $this->getKeys($fb_ads_data, 'gender', $gender);
                            }
                        }

                        $ages_clicks = [];
                        if (count($ages_keys) > 0) {
                            foreach ($ages_keys as $agekey => $age_key) {
                                $age_click_data = 0;
                                foreach ($age_key as $age_click_key) {
                                    $age_click_data += $fb_ads_data[$age_click_key]['clicks'];
                                }
                                $ages_clicks[$agekey] = $age_click_data;
                            }
                        }

                        $genders_clicks = [];
                        if (count($genders_keys) > 0) {
                            foreach ($genders_keys as $genderkey => $gender_key) {
                                $gender_click_data = 0;
                                foreach ($gender_key as $gender_click_key) {
                                    $gender_click_data += $fb_ads_data[$gender_click_key]['clicks'];
                                }
                                $genders_clicks[$genderkey] = $gender_click_data;
                            }
                        }

                        if (count($ages_clicks) > 0) {
                            $ages_graph_url = getChartUrl($ages_clicks);
                        }

                        if (count($genders_clicks) > 0) {
                            $genders_graph_url = getChartUrl($genders_clicks);
                        }

                    }
                    //$html = view('reports.templates.facebook', compact('report', 'campaigns_insights', 'total_clicks', 'total_impressions', 'total_ctr', 'total_cpm', 'total_cpc', 'total_spend', 'ages_graph_url', 'genders_graph_url'))->render();
                    foreach ($recipients as $email) {
                        $welcome_email_substitutions = [
                            '%frequency%' => (string) $report->frequency,
                            '%report_date%' => (string) date('m/d/Y'),
                            '%visitors%' => (string) $total_clicks,
                            '%avg_time%' => (string) $total_impressions,
                            '%bounce_rate%' => (string) $total_ctr,
                            '%page_views%' => (string) $total_spend,
                            '%page_per_visits%' => (string) $total_cpm,
                            '%new_visitors%' => (string) $total_cpc,
                            '%ages_graph_url%' => (string) $ages_graph_url,
                            '%genders_graph_url%' => (string) $genders_graph_url,
                            '%top_ad_campaigns%' => (string) $top_ad_campaigns,
                        ];
                        sendMail($email, 'Your ' . ucfirst($report->frequency) . ' Facebook Ads Report', '56c13cc8-0a27-40e0-bd31-86ffdced98ae', $welcome_email_substitutions);
                        Schedule::create([
                            'user_id' => $report->user_id,
                            'report_id' => $report->id,
                            'recipient' => $email,
                        ]);
                    }
                } catch (AuthorizationException $e) {
                }
            }
            if ($report->account->type == 'analytics') {
                exit;
                $from_date = '';
                $to_date = date('Y-m-d', strtotime($report->next_send_time));
                switch ($report->frequency) {
                    case "weekly":
                        $from_date = date('Y-m-d', strtotime('-7 day', strtotime($report->next_send_time)));
                        break;
                    case "monthly":
                        $from_date = date('Y-m-d', strtotime('-1 month', strtotime($report->next_send_time)));
                        break;
                    case "yearly":
                        $from_date = date('Y-m-d', strtotime('-1 year', strtotime($report->next_send_time)));
                        break;
                    default:
                        $from_date = 'today';
                        $to_date = 'today';
                }
                $client = analytics_connect();
                $client->setAccessToken(analytics_token());
                $analytics = new \Google_Service_Analytics($client);
                $params = array(
                    'dimensions' => 'ga:deviceCategory,ga:country',
                    //'sort' => '-ga:sessions,ga:country',
                    //'max-results' => '5'
                );

                $results = $analytics->data_ga->get(
                    'ga:' . $report->profile->view_id, $from_date, $to_date, 'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:bounceRate,ga:newUsers,ga:sessionsPerUser', $params);
                $insights = $results->totalsForAllResults;
                $metrics = $results->rows;
                if ($metrics && count($metrics) > 0) {
                    $final_metrics_array = [];
                    $operating_system_result = [];
                    $locations_result = [];
                    foreach ($metrics as $metric) {
                        $operating_system_result[] = $metric[0];
                        $locations_result[] = $metric[1];
                    }
                    $operating_systems = array_unique($operating_system_result);
                    $locations = array_unique($locations_result);
                    pr($metrics);
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

    public function getKeys($haystack, $field, $value)
    {
        $keys = [];
        foreach ($haystack as $key => $array) {
            if ($array[$field] === $value) {
                $keys[] = $key;
            }

        }
        return $keys;
    }

}
