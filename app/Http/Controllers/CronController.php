<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Session;
use \FacebookAds\Http\Exception\AuthorizationException;
use \FacebookAds\Object\Fields\AdsInsightsFields;
use \FacebookAds\Object\Values\AdsInsightsBreakdownsValues;
use \FacebookAds\Object\Values\AdsInsightsDatePresetValues;

class CronController extends Controller
{

    public function report($id)
    {
        $report = Report::where('id', $id)
            ->with('account', 'ad_account', 'property', 'profile')
            ->first();
        if ($report) {
            if ($report->account->type == 'facebook') {
                $params = array(
                    'breakdowns' => [AdsInsightsBreakdownsValues::AGE, AdsInsightsBreakdownsValues::GENDER],
                );
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
                    $fb_ad_account = new \FacebookAds\Object\AdAccount($report->ad_account->ad_account_id);
                } catch (\InvalidArgumentException $e) {
                    Session::flash('alert-danger', 'Invalid account.');
                    return redirect()->route('reports.index');
                }
                $fields = $this->fbDataFields();
                $fields = [$fields->clicks, $fields->impressions, $fields->ctr, $fields->cpc, $fields->cpm, $fields->spend];
                try {
                    $insights = $fb_ad_account->getInsights($fields, $params);
                } catch (AuthorizationException $e) {
                    Session::flash('alert-danger', $e->getMessage());
                    return redirect()->route('reports.index');
                }
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
            }
            if ($report->account->type == 'analytics') {
                $client = analytics_connect();
                $client->setAccessToken(analytics_token());
                $analytics = new \Google_Service_Analytics($client);
                $results = $analytics->data_ga->get(
                    'ga:' . $report->profile->view_id,
                    'today',
                    'today',
                    'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:bounceRate,ga:newUsers,ga:sessionsPerUser');
                pr($results->totalsForAllResults);
                exit;
            }
        } else {
            Session::flash('alert-danger', 'Report not found.');
            return redirect()->route('reports.index');
        }

    }

    public function fbDataFields()
    {
        $fields = [
            'clicks' => AdsInsightsFields::CLICKS,
            'impressions' => AdsInsightsFields::IMPRESSIONS,
            'ctr' => AdsInsightsFields::CTR,
            'cpm' => AdsInsightsFields::CPM,
            'cpc' => AdsInsightsFields::CPC,
            'spend' => AdsInsightsFields::SPEND,
        ];
        return (object) $fields;
    }
}
