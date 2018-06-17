<?php

namespace App\Http\Controllers;

set_time_limit(0);

use App\Models\Geo;
use App\Models\Plan;
use App\Models\Report;
use App\Models\Schedule;
use date;
use Session;
use \FacebookAds\Http\Exception\AuthorizationException;
use \FacebookAds\Object\AdAccount;
use \FacebookAds\Object\Fields\AdsInsightsFields;
use \FacebookAds\Object\Fields\CampaignFields;
use \FacebookAds\Object\Values\AdsInsightsBreakdownsValues;
use \FacebookAds\Object\Values\AdsInsightsDatePresetValues;

class CronController extends Controller
{
    private $sendLimit = 0;

    public function run()
    {
        $pdf_dir = public_path('files/pdf/');
        if (!is_dir($pdf_dir)) {
            mkdir($pdf_dir, 0777, true);
        }
        $next_send_time = date('Y-m-d H:i:00');

        $reports = Report::where('next_send_time', $next_send_time)->where('is_active', 1)->where('is_paused', 0)->with('user', 'account', 'ad_account', 'property', 'profile')->get();
        //$reports = Report::where('id', 385)->with('user', 'account', 'ad_account', 'property', 'profile')->get();
        if ($reports && count($reports) > 0) {
            foreach ($reports as $report) {
                $current_plan = $report->user->current_billing_plan ? $report->user->current_billing_plan : 'free_trial';
                $plan = Plan::whereTitle($current_plan)->first();
                $reports_sent_count = Schedule::whereUserId($report->user->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
                if ($reports_sent_count < $plan->reports) {
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
        $pdf_file_name = time() . '.pdf';
        $pdf_dir = public_path('files/pdf/');

        $logo = "https://marketing-image-production.s3.amazonaws.com/uploads/6a85ebdabdfb77a126cb7bf00bc4e6d57311fe1066183a13b069ef4c4d45787aeca594f03de289f41878cb97923a4cd1ef1d77b4d1409a7d10c00fb4b2cfab8b.png";
        $logoCheck = $report->user->logo;
        if ($logoCheck != 'logo.png') {
            $logo = $logoCheck;
        }
        $ad_account_title = $report->ad_account->title;
        if ($report) {
            if ($report->account->type == 'facebook') {

                $params = [];
                $report->user->timezone ? date_default_timezone_set($report->user->timezone) : '';
                $reportDate = date("m/d/Y");
                date_default_timezone_set('Europe/London');
                switch ($report->frequency) {
                    case "weekly":
                        $params['date_preset'] = AdsInsightsDatePresetValues::LAST_7D;
                        $reportDate = date("m/d/Y", strtotime("-7 Days")) . "-" . date("m/d/Y");
                        break;
                    case "monthly":
                        $params['date_preset'] = AdsInsightsDatePresetValues::LAST_30D;
                        $reportDate = date("m/d/Y", strtotime("-30 Days")) . "-" . date("m/d/Y");
                        break;
                    case "yearly":
                        $endDate = date('Y-m-d', strtotime(date('Y') . '-' . $report->ends_at));
                        $startDate = date('Y-m-d', strtotime('-1 year', strtotime($endDate)));
                        $params['time_range'] = ("{'since': '" . $startDate . "', 'until': '" . $endDate . "'}");
                        $reportDate = date("Y");
                        break;
                    default:
                        $params['date_preset'] = AdsInsightsDatePresetValues::TODAY;
                }
                \FacebookAds\Api::init(env('FACEBOOK_APP_ID'), env('FACEBOOK_SECRET'), fb_token($report->user_id));
                try {
                    $fb_ad_account = new AdAccount($report->ad_account->ad_account_id);
                } catch (\InvalidArgumentException $e) {
                    echo 'fb error';
                }

                $fields = [AdsInsightsFields::CLICKS, AdsInsightsFields::IMPRESSIONS, AdsInsightsFields::CTR, AdsInsightsFields::CPM, AdsInsightsFields::CPC, AdsInsightsFields::SPEND];
                try {
                    // Get Campaigns
                    $campaigns = $fb_ad_account->getCampaigns([CampaignFields::ID, CampaignFields::NAME], ['limit' => 5]);
                    $campaigns_insights = [];
                    if ($campaigns && count($campaigns) > 0) {
                        $encodedString = json_encode($campaigns);

                        //Save the JSON string to a text file.
                        file_put_contents('facebook_array.txt', $encodedString);

                        foreach ($campaigns as $campaign) {
                            $cinsights = $campaign->getInsights($fields, $params);
                            foreach ($cinsights as $insight) {
                                $campaigns_insights[] = array_merge([
                                    'clicks' => round($insight->clicks, 2),
                                    'impressions' => round($insight->impressions, 2),
                                    'ctr' => round($insight->ctr, 2),
                                    'cpc' => round($insight->cpc, 2),
                                    'cpm' => round($insight->cpm, 2),
                                    'spend' => round($insight->spend, 2),
                                    'date_start' => round($insight->date_start),
                                    'date_stop' => round($insight->date_stop),
                                ], [
                                    'campaign' => $campaign->{CampaignFields::NAME},
                                ]);
                            }

                        }
                    }
                    $top_ad_campaigns = '';
                    if (count($campaigns_insights) > 0) {
                        foreach ($campaigns_insights as $campaign_insight) {
                            $campaign_insight = (object)$campaign_insight;
                            $campaign_insight_cpm = number_format((float)$campaign_insight->cpm, 2, '.', '');
                            $top_ad_campaigns .= '<tr style="border-bottom:1px solid #ccc"><td style="padding:5px;">' . $campaign_insight->campaign . '</td><td style="padding:5px;">' . $campaign_insight->impressions . '</td><td style="padding:5px;">' . $campaign_insight->clicks . '</td><td style="padding:5px;">$' . $campaign_insight_cpm . '</td><td style="padding:5px;">' . $campaign_insight->ctr . '%</td><td style="padding:5px;">$' . $campaign_insight->cpc . '</td><td style="padding:5px;">$' . $campaign_insight->spend . '</td></tr>';
                        }
                    } else {
                        $top_ad_campaigns = '<tr><h3><center>No data</center></h3></tr>';
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
                            $total_clicks += round($insight->clicks, 2);
                            $total_impressions += round($insight->impressions, 2);
                            $total_ctr += round($insight->ctr, 2);
                            $total_cpm += round($insight->cpm, 2);
                            $total_cpc += round($insight->cpc, 2);
                            $total_spend += round($insight->spend, 2);
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
                    }
                    if ($report->attachment_type == 'pdf') {
                        $html = view('reports.templates.facebook', compact('report', 'campaigns_insights', 'total_clicks', 'total_impressions', 'total_ctr', 'total_cpm', 'total_cpc', 'total_spend', 'ages_graph_url', 'genders_graph_url', 'ad_account_title'))->render();
                        $mpdf = new \Mpdf\Mpdf(['tempDir' => $pdf_dir]);
                        $mpdf->WriteHTML($html);
                        $mpdf->Output($pdf_dir . $pdf_file_name, 'F');
                        $attachments = [
                            [
                                'content' => base64_encode(file_get_contents($pdf_dir . $pdf_file_name)),
                                'type' => 'text/pdf',
                                'filename' => $report->title . '.pdf',
                                'disposition' => 'attachment',
                            ],
                        ];
                    }
                    foreach ($recipients as $email) {
                        $welcome_email_substitutions = [
                            '%frequency%' => (string)ucfirst($report->frequency),
                            '%report_date%' => (string)$reportDate,
                            '%visitors%' => (string)$total_clicks,
                            '%avg_time%' => (string)$total_impressions,
                            '%bounce_rate%' => (string)$total_ctr,
                            '%page_views%' => (string)$total_spend,
                            '%page_per_visits%' => (string)$total_cpm,
                            '%new_visitors%' => (string)$total_cpc,
                            '%ages_graph_url%' => (string)$ages_graph_url,
                            '%genders_graph_url%' => (string)$genders_graph_url,
                            '%top_ad_campaigns%' => (string)$top_ad_campaigns,
                            '%property_url%' => $report->ad_account->title,
                            '%logo_property%' => $logo,
                        ];
                        if ($report->attachment_type == 'pdf') {
                            $sent = sendMail($email, $report->email_subject, '56c13cc8-0a27-40e0-bd31-86ffdced98ae', $welcome_email_substitutions, $attachments);
                            if (file_exists($pdf_dir . $pdf_file_name)) {
                                unlink($pdf_dir . $pdf_file_name);
                            }
                        } else {
                            $sent = sendMail($email, $report->email_subject, '56c13cc8-0a27-40e0-bd31-86ffdced98ae', $welcome_email_substitutions);
                        }
                        Schedule::create([
                            'user_id' => $report->user_id,
                            'report_id' => $report->id,
                            'recipient' => $email,
                        ]);
                        $this->sendLimit($report);
                        $encodedString = json_encode($sent);

                        file_put_contents('facebook_sent.txt', $encodedString);
                    }
                } catch (AuthorizationException $e) {
                }
            }
            if ($report->account->type == 'analytics') {
                $to_date = date('Y-m-d', strtotime($report->next_send_time));
                $report->user->timezone ? date_default_timezone_set($report->user->timezone) : '';
                $reportDate = date("m/d/Y");
                date_default_timezone_set('Europe/London');
                switch ($report->frequency) {
                    case "weekly":
                        $from_date = date('Y-m-d', strtotime('-7 day', strtotime($report->next_send_time)));
                        $reportDate = date("m/d/Y", strtotime("-7 Days")) . "-" . date("m/d/Y");
                        break;
                    case "monthly":
                        $from_date = date('Y-m-d', strtotime('-1 month', strtotime($report->next_send_time)));
                        $reportDate = date("m/d/Y", strtotime("-30 Days")) . "-" . date("m/d/Y");
                        break;
                    case "yearly":
                        $from_date = date('Y-m-d', strtotime('-1 year', strtotime($report->next_send_time)));
                        $reportDate = date("Y");
                        break;
                    default:
                        $from_date = 'today';
                        $to_date = 'today';
                }

                $client = analytics_connect();
                $client->setAccessToken(analytics_token($report->user_id));
                $analytics = new \Google_Service_Analytics($client);
                // Get top 5 sources
                $top_sources_results = $analytics->data_ga->get(
                    'ga:' . $report->profile->view_id, $from_date, $to_date, 'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:avgTimeOnPage,ga:bounceRate,ga:newUsers,ga:sessionsPerUser,ga:itemRevenue', [
                    'dimensions' => 'ga:source',
                    'sort' => '-ga:sessions',
                    'max-results' => 5,
                ]);
                $top_5_sources = '';
                $sources_insights = isset($top_sources_results->rows) ? $top_sources_results->rows : [];
                if (isset($sources_insights) && count($sources_insights) > 0) {

                    $top_5_sources .= '<table width="100%" cellpadding="5" cellspacing="0" style="background:#fff"><tbody><tr><th style="background:#666;color:#fff;padding:5px;text-align:left;">Source</th><th style="background:#666;color:#fff;padding:5px;text-align:left;">Visits</th><th style="background:#666;color:#fff;padding:5px;text-align:left;">New</th><th style="background:#666;color:#fff;padding:5px;text-align:left;">Bounce %</th><th style="background:#666;color:#fff;padding:5px;text-align:left;">Pages/Visit</th><th style="background:#666;color:#fff;padding:5px;text-align:left;">Revenue</th></tr>';
                    foreach ($sources_insights as $insight) {
                        $new_visitors = round($insight[6], 0) . "";
                        $pages_per_visit = number_format((float)$insight[7], 2, '.', '');
                        $bounce_rate = round($insight[5], 0);
                        $avg_time = date("H:i:s", strtotime($insight[3]));
                        $revenue = $insight[8];
                        $top_5_sources .= '<tr><td>' . $insight[0] . '</td><td>' . $insight[1] . '</td><td>' . $new_visitors . '</td><td>' . $bounce_rate . '%</td><td>' . $pages_per_visit . '</td><td>$' . $revenue . '</td></tr>';
                    }
                    $top_5_sources .= '</tbody></table>';
                } else {
                    $top_5_sources = '<tr><h3><center>No data</center></h3></tr>';
                }

                $results = $analytics->data_ga->get(
                    'ga:' . $report->profile->view_id, $from_date, $to_date, 'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:avgTimeOnPage,ga:bounceRate,ga:newUsers,ga:sessionsPerUser,ga:itemRevenue', ['dimensions' => 'ga:deviceCategory,ga:country']);

                $encodedString = json_encode($results);

                //Save the JSON string to a text file.
                //  file_put_contents('analytics_array.txt', $encodedString);

                $insights = $results->totalsForAllResults;
                $metrics = $results->rows;
                $total_sessions = 'No data';
                $total_pageviews = 'No data';
                $total_avg_time = 'No data';
                $total_bounce_rate = 'No data';
                $total_new_visitors = 'No data';
                $total_pages_per_visitor = 'No data';
                $total_revenue = 'No data';
                $google_analytics_ads_data = [];
                $locations_graph_url = 'no_data';
                $devices_graph_url = 'no_data';

                if (isset($insights) && $insights) {
                    $total_sessions = number_format($insights['ga:sessions']);
                    $total_pageviews = number_format($insights['ga:pageviews']);
                    $total_avg_time = $insights['ga:avgTimeOnPage'];
                    $total_bounce_rate = round($insights['ga:bounceRate'], 2);
                    $total_new_visitors = number_format($insights['ga:newUsers']);
                    $total_pages_per_visitor = round($insights['ga:sessionsPerUser'], 2);
                    $total_revenue = $insights['ga:itemRevenue'];
                }
                if ($metrics && count($metrics) > 0) {
                    $operating_system_result = [];
                    $locations_result = [];
                    foreach ($metrics as $metric) {
                        $operating_system_result[] = $metric[0];
                        $locations_result[] = $metric[1];
                        $google_analytics_ads_data[] = [
                            'operating_system' => $metric[0],
                            'location' => $metric[1],
                            'sessions' => $metric[2],
                            'pageviews' => $metric[3],
                            'avg_time' => $metric[4],
                            'bounce_rate' => $metric[5],
                            'new_visitors' => $metric[6],
                            'pages_per_visitor' => $metric[7],
                            'total_revenue' => $metric[8],
                        ];
                    }

                    $operating_systems = [];
                    $locations = [];
                    if (count($google_analytics_ads_data) > 0) {
                        if (count($operating_system_result) > 0) {
                            $operating_systems = array_unique($operating_system_result);
                        }

                        if (count($locations_result) > 0) {
                            $locations = array_unique($locations_result);
                        }

                        $operating_systems_keys = [];
                        if (count($operating_systems) > 0) {
                            foreach ($operating_systems as $operating_system) {
                                $operating_systems_keys[$operating_system] = $this->getKeys($google_analytics_ads_data, 'operating_system', $operating_system);
                            }
                        }

                        $locations_keys = [];
                        if (count($locations) > 0) {
                            foreach ($locations as $location) {
                                $locations_keys[$location] = $this->getKeys($google_analytics_ads_data, 'location', $location);
                            }
                        }

                        $operating_systems_clicks = [];
                        if (count($operating_systems_keys) > 0) {
                            foreach ($operating_systems_keys as $operatingsystemkey => $operating_system_key) {
                                $operating_system_click_data = 0;
                                foreach ($operating_system_key as $operating_system_session_key) {
                                    $operating_system_click_data += $google_analytics_ads_data[$operating_system_session_key]['sessions'];
                                }
                                $operating_systems_clicks[$operatingsystemkey] = $operating_system_click_data;
                            }
                        }

                        $locations_clicks = [];
                        if (count($locations_keys) > 0) {
                            foreach ($locations_keys as $locationkey => $location_key) {
                                $location_click_data = 0;
                                foreach ($location_key as $location_session_key) {
                                    $location_click_data += $google_analytics_ads_data[$location_session_key]['sessions'];
                                }
                                $locations_clicks[$locationkey] = $location_click_data;
                            }
                        }

                        if (count($operating_systems_clicks) > 0) {
                            $devices_graph_url = getChartUrl($operating_systems_clicks);
                        }

                        if (count($locations_clicks) > 0) {
                            arsort($locations_clicks);
                            $locations_graph_url = getChartUrl(array_slice($locations_clicks, 0, 5));
                        }
                    }
                }

                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.analytics', compact('report', 'sources_insights', 'total_sessions', 'total_revenue', 'total_bounce_rate', 'total_pageviews', 'total_pages_per_visitor', 'total_new_visitors', 'devices_graph_url', 'locations_graph_url', 'ad_account_title'))->render();
                    $mpdf = new \Mpdf\Mpdf(['tempDir' => $pdf_dir]);
                    $mpdf->WriteHTML($html);
                    $mpdf->Output($pdf_dir . $pdf_file_name, 'F');
                    $attachments = [
                        [
                            'content' => base64_encode(file_get_contents($pdf_dir . $pdf_file_name)),
                            'type' => 'text/pdf',
                            'filename' => $report->title . '.pdf',
                            'disposition' => 'attachment',
                        ],
                    ];
                }

                foreach ($recipients as $email) {

                    $analytics_email_substitutions = [
                        '%frequency%' => (string)ucfirst($report->frequency),
                        '%report_date%' => (string)$reportDate,
                        '%visitors%' => (string)$total_sessions,
                        '%avg_time%' => (string)gmdate("H:i:s", strtotime($total_avg_time) * 3600),
                        '%revenue%' => (string)$total_revenue,
                        '%bounce_rate%' => (string)$total_bounce_rate,
                        '%page_views%' => (string)$total_pageviews,
                        '%page_per_visits%' => (string)$total_pages_per_visitor,
                        '%new_visitors%' => (string)$total_new_visitors,
                        '%devices_graph_url%' => (string)$devices_graph_url,
                        '%locations_graph_url%' => (string)$locations_graph_url,
                        '%top_5_sources%' => (string)$top_5_sources,
                        '%property_url%' => (string)$report->property->name,
                        '%logo_property%' => $logo,
                    ];

                    $encodedString = json_encode($analytics_email_substitutions);

                    //Save the JSON string to a text file.
                    //file_put_contents('analytics_send_array.txt', $encodedString);
                    if ($report->attachment_type == 'pdf') {
                        $sent = sendMail($email, $report->email_subject, 'a62644eb-9c36-40bf-90f5-09addbbef798', $analytics_email_substitutions, $attachments);
                        if (file_exists($pdf_dir . $pdf_file_name)) {
                            unlink($pdf_dir . $pdf_file_name);
                        }
                    } else {
                        $sent = sendMail($email, $report->email_subject, 'a62644eb-9c36-40bf-90f5-09addbbef798', $analytics_email_substitutions);
                    }
                    Schedule::create([
                        'user_id' => $report->user_id,
                        'report_id' => $report->id,
                        'recipient' => $email,
                    ]);
                    $this->sendLimit($report);
                    $encodedString = json_encode($sent);

                    // file_put_contents('analytics_sent.txt', $encodedString);
                }
            }
            if ($report->account->type == 'adword') {
                $report->user->timezone ? date_default_timezone_set($report->user->timezone) : '';
                $reportDate = date("m/d/Y");
                date_default_timezone_set('Europe/London');
                switch ($report->frequency) {
                    case "weekly":
                        $during = 'LAST_7_DAYS';
                        $reportDate = date("m/d/Y", strtotime("-7 Days")) . "-" . date("m/d/Y");
                        break;
                    case "monthly":
                        $to_date = date('Ymd', strtotime($report->next_send_time));
                        $from_date = date('Ymd', strtotime('-1 month', strtotime($report->next_send_time)));
                        $during = $from_date . ',' . $to_date;
                        $reportDate = date("m/d/Y", strtotime("-30 Days")) . "-" . date("m/d/Y");
                        break;
                    case "yearly":
                        $to_date = date('Ymd', strtotime($report->next_send_time));
                        $from_date = date('Ymd', strtotime('-1 year', strtotime($report->next_send_time)));
                        $during = $from_date . ',' . $to_date;
                        $reportDate = date("Y");
                        break;
                    default:
                        $during = 'TODAY';
                }
                $session = adwords_session($report->ad_account->ad_account_id, $report->user_id);
                $reportQuery = 'SELECT CampaignName, Clicks, Impressions, Ctr, Cost, AverageCpm, AverageCpc , CountryCriteriaId, Device FROM GEO_PERFORMANCE_REPORT DURING ' . $during;

                $reportDownloader = new \Google\AdsApi\AdWords\Reporting\v201710\ReportDownloader($session);
                $reportSettingsOverride = (new \Google\AdsApi\AdWords\ReportSettingsBuilder())
                    ->includeZeroImpressions(false)
                    ->build();
                $reportDownloadResult = $reportDownloader->downloadReportWithAwql(
                    $reportQuery, \Google\AdsApi\AdWords\Reporting\v201710\DownloadFormat::CSV, $reportSettingsOverride);

                $campaigns_adword_data = str_getcsv($reportDownloadResult->getAsString(), "\n");

                $encodedString = json_encode($campaigns_adword_data);

                //  file_put_contents('adwords_array.txt', $encodedString);

                $total_clicks = 'No data';
                $total_impressions = 'No data';
                $total_ctr = 'No data';
                $total_cpm = 'No data';
                $total_cpc = 'No data';
                $total_spend = 'No data';
                $adwords_ads_data = [];
                $devices_graph_url = 'no_data';
                $locations_graph_url = 'no_data';
                $final_adword_data = [];
                $top_5_campaigns = '';
                if (is_array($campaigns_adword_data) && count($campaigns_adword_data) > 0) {
                    foreach ($campaigns_adword_data as $report_data_key => $report_data) {
                        if ($report_data_key === 0 || $report_data_key === 1) {
                            continue;
                        }
                        $final_adword_data[] = explode(',', $report_data);
                    }
                    $top_5_campaigns_array = [];
                    $google_adwords_ads_data = [];
                    if (count($final_adword_data) > 0) {
                        $operating_system_result = [];
                        $locations_result = [];
                        $adword_data_count = count($final_adword_data);
                        $encodedString = json_encode($final_adword_data);

                        //  file_put_contents('adwords_array2.txt', $encodedString);
                        foreach ($final_adword_data as $adword_data) {
                            if (--$adword_data_count <= 0) {
                                break;
                            }
                            $locations_result[] = $adword_data[7];
                            $operating_system_result[] = $adword_data[8];
                            $google_adwords_ads_data[] = [
                                'clicks' => $adword_data[1],
                                'impressions' => $adword_data[2],
                                'ctr' => $adword_data[3],
                                'spend' => $adword_data[4],
                                'cpc' => $adword_data[5],
                                'cpm' => $adword_data[6],
                                'location' => $adword_data[7],
                                'operating_system' => $adword_data[8],
                            ];
                        }
                        $operating_systems = [];
                        $locations = [];
                        if (count($operating_system_result) > 0) {
                            $operating_systems = array_unique($operating_system_result);
                        }

                        if (count($locations_result) > 0) {
                            $locations = array_unique($locations_result);
                        }

                        $operating_systems_keys = [];
                        if (count($operating_systems) > 0) {
                            foreach ($operating_systems as $operating_system) {
                                $operating_systems_keys[$operating_system] = $this->getKeys($google_adwords_ads_data, 'operating_system', $operating_system);
                            }
                        }

                        $locations_keys = [];
                        if (count($locations) > 0) {
                            foreach ($locations as $location) {
                                $locations_keys[$location] = $this->getKeys($google_adwords_ads_data, 'location', $location);
                            }
                        }

                        $operating_systems_clicks = [];
                        if (count($operating_systems_keys) > 0) {
                            foreach ($operating_systems_keys as $operatingsystemkey => $operating_system_key) {
                                $operating_system_click_data = 0;
                                foreach ($operating_system_key as $operating_system_session_key) {
                                    $operating_system_click_data += $google_adwords_ads_data[$operating_system_session_key]['clicks'];
                                }
                                $operating_systems_clicks[$operatingsystemkey] = $operating_system_click_data;
                            }
                        }

                        $locations_clicks = [];
                        if (count($locations_keys) > 0) {
                            foreach ($locations_keys as $locationkey => $location_key) {
                                $location_click_data = 0;
                                foreach ($location_key as $location_session_key) {
                                    $location_click_data += $google_adwords_ads_data[$location_session_key]['clicks'];
                                }
                                $locations_clicks[$locationkey] = $location_click_data;
                            }
                        }
                        if (count($operating_systems_clicks) > 0) {
                            $devices_graph_url = getChartUrl($operating_systems_clicks);
                        }

                        if (count($locations_clicks) > 0) {
                            arsort($locations_clicks);
                            $top_5_locations = array_slice($locations_clicks, 0, 5, true);
                            $final_locations_data = [];
                            foreach ($top_5_locations as $country_id => $clicks) {
                                $geo = Geo::select('country_code')->where('parent_id', $country_id)->first();
                                $country = 'N/A';
                                if ($geo) {
                                    $country = $geo->country_code;
                                }
                                $final_locations_data[$country] = $clicks;
                            }
                            $locations_graph_url = getChartUrl($final_locations_data);
                        }

                        $total_data = end($final_adword_data);
                        $total_clicks = $total_data[1];
                        $total_impressions = $total_data[2];
                        $total_ctr = $total_data[3];
                        $total_spend = $total_data[4];
                        $total_cpm = number_format(($total_data[5] / 1000000), 2);
                        $total_cpc = number_format(($total_data[6] / 100000), 2);
                        $top_5_campaigns_array = array_slice($final_adword_data, 0, 5);
                    }
                    if (count($top_5_campaigns_array) > 0) {
                        $top_5_campaigns .= '<table width="100%" cellpadding="5" cellspacing="0" style="background:#fff"><tbody><tr><th style="background:#666;color:#fff;padding:5px;">Campaign</th><th style="background:#666;color:#fff;padding:5px;">Clicks</th><th style="background:#666;color:#fff;padding:5px;">Impressions</th><th style="background:#666;color:#fff;padding:5px;">CTR</th><th style="background:#666;color:#fff;padding:5px;">CPM</th><th style="background:#666;color:#fff;padding:5px;">CPC</th></tr>';
                        foreach ($top_5_campaigns_array as $campaign_array) {
                            $ad_cpc = number_format((float)($campaign_array[6] / 100000), 2);
                            $ad_cpm = number_format((float)($campaign_array[5] / 100000), 2);
                            $top_5_campaigns .= '<tr><td>' . $campaign_array[0] . '</td><td>' . $campaign_array[1] . '</td><td>' . $campaign_array[2] . '</td><td>' . $campaign_array[3] . '</td><td>$' . $ad_cpm . '</td><td>$' . $ad_cpc . '</td></tr>';
                        }
                        $top_5_campaigns .= '</tbody></table>';
                    } else {
                        $top_5_campaigns = '<tr><h3><center>No data</center></h3></tr>';
                    }
                }

                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.adword', compact('report', 'total_clicks', 'total_impressions', 'total_ctr', 'total_cpm', 'total_cpc', 'total_spend', 'devices_graph_url', 'locations_graph_url', 'top_5_campaigns', 'ad_account_title'))->render();
                    $mpdf = new \Mpdf\Mpdf(['tempDir' => $pdf_dir]);
                    $mpdf->WriteHTML($html);
                    $mpdf->Output($pdf_dir . $pdf_file_name, 'F');
                    $attachments = [
                        [
                            'content' => base64_encode(file_get_contents($pdf_dir . $pdf_file_name)),
                            'type' => 'text/pdf',
                            'filename' => $report->title . '.pdf',
                            'disposition' => 'attachment',
                        ],
                    ];
                }

                foreach ($recipients as $email) {
                    $welcome_email_substitutions = [
                        '%frequency%' => (string)ucfirst($report->frequency),
                        '%report_date%' => (string)$reportDate,
                        '%property_url%' => $report->ad_account->title,
                        '%clicks%' => (string)$total_clicks,
                        '%impressions%' => (string)$total_impressions,
                        '%ctr%' => (string)$total_ctr,
                        '%spend%' => (string)number_format((float)($total_spend / 1000000), 2),
                        '%page_per_visits%' => (string)$total_cpm,
                        '%new_visitors%' => (string)$total_cpc,
                        '%devices_graph_url%' => (string)$devices_graph_url,
                        '%locations_graph_url%' => (string)$locations_graph_url,
                        '%top_5_campaigns%' => (string)$top_5_campaigns,
                        '%logo_property%' => $logo,
                    ];
                    if ($report->attachment_type == 'pdf') {
                        $sent = sendMail($email, $report->email_subject, '0a98196e-646c-45ff-af50-5826009e72ab', $welcome_email_substitutions, $attachments);
                        if (file_exists($pdf_dir . $pdf_file_name)) {
                            unlink($pdf_dir . $pdf_file_name);
                        }
                    } else {
                        $sent = sendMail($email, $report->email_subject, '0a98196e-646c-45ff-af50-5826009e72ab', $welcome_email_substitutions);
                    }
                    Schedule::create([
                        'user_id' => $report->user_id,
                        'report_id' => $report->id,
                        'recipient' => $email,
                    ]);
                    $this->sendLimit($report);
                    $encodedString = json_encode($sent);

                    //   file_put_contents('adwords_sent.txt', $encodedString);
                }
            }
            if ($report->account->type == 'stripe') {
                $report->user->timezone ? date_default_timezone_set($report->user->timezone) : '';
                $reportDate = strtotime($report->next_send_time);
                switch ($report->frequency) {
                    case "weekly":
                        $from_date = strtotime('-7 day', $reportDate);
                        break;
                    case "monthly":
                        $from_date = strtotime('-1 month', $reportDate);
                        break;
                    case "yearly":
                        $from_date = strtotime('-1 year', $reportDate);
                        break;
                    default:
                        $from_date = strtotime(date('m/d/Y'));
                }
                \Stripe\Stripe::setApiKey(env('STRIPE_SECRET'));
                try {
                    $balance = \Stripe\Balance::retrieve(['stripe_account' => $report->ad_account->ad_account_id]);
                } catch (\Stripe\Error\Permission  $e) {
                    $report->account()->update(['status' => 0]);
                }
                $total_balance = 0;
                if (isset($balance->available[0]->amount)) {
                    $total_balance = calculateStripeAmount($balance->available[0]->amount);
                }
                $charges = \Stripe\BalanceTransaction::all([
                    'limit' => 100,
                    'type' => 'charge',
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $refunds = \Stripe\BalanceTransaction::all([
                    'limit' => 100,
                    'type' => 'refund',
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $disputes = \Stripe\Dispute::all([
                    'limit' => 100,
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $customers = \Stripe\Customer::all([
                    'limit' => 100,
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $payouts = \Stripe\Payout::all([
                    'limit' => 100,
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $payments = \Stripe\BalanceTransaction::all([
                    'created' => [
                        'gte' => $from_date,
                        'lte' => $reportDate
                    ]
                ], ['stripe_account' => $report->ad_account->ad_account_id]);
                $total_charge_amount = 0;
                $total_refund_amount = 0;
                $total_dispute_amount = 0;
                $total_payout_amount = 0;
                $total_customers = 0;
                foreach ($charges->autoPagingIterator() as $charge) {
                    $total_charge_amount += $charge->amount;
                }
                foreach ($refunds->autoPagingIterator() as $refund) {
                    $total_refund_amount += $refund->amount;
                }
                if (isset($disputes['data']) && count($disputes['data'])) {
                    foreach ($disputes['data'] as $dispute) {
                        $total_dispute_amount += $dispute->amount;
                    }
                }
                if (isset($customers['data']) && count($customers['data'])) {
                    $total_customers = count($customers['data']);
                }
                foreach ($payouts->autoPagingIterator() as $payout) {
                    $total_payout_amount += $payout->amount;
                }
                $total_payments = 0;
                if (isset($payments['data']) && count($payments['data'])) {
                    $total_payments = count($payments['data']);
                }
                if ($total_customers) {
                    $stripe_customers_html = view('reports.templates.stripe.customers', compact('customers'))->render();
                } else {
                    $stripe_customers_html = 'No data for this time period';
                }
                if ($total_payments) {
                    $stripe_transactions_html = view('reports.templates.stripe.transactions', compact('payments'))->render();
                } else {
                    $stripe_transactions_html = 'No data for this time period';
                }
                $attachments = [];
                if ($report->attachment_type == 'pdf') {
                    $html = view('reports.templates.stripe', compact('report', 'stripe_transactions_html', 'total_customers', 'total_charge_amount', 'total_refund_amount', 'total_dispute_amount', 'total_payout_amount', 'stripe_customers_html', 'ad_account_title', 'total_balance', 'logo'))->render();
                    $mpdf = new \Mpdf\Mpdf(['tempDir' => $pdf_dir]);
                    $mpdf->WriteHTML($html);
                    $mpdf->Output($pdf_dir . $pdf_file_name, 'F');
                    $attachments = [
                        [
                            'content' => base64_encode(file_get_contents($pdf_dir . $pdf_file_name)),
                            'type' => 'text/pdf',
                            'filename' => $report->title . '.pdf',
                            'disposition' => 'attachment',
                        ],
                    ];
                }
                foreach ($recipients as $email) {
                    $stripe_email_substitutions = [
                        '%frequency%' => (string)ucfirst($report->frequency),
                        '%report_date%' => (string)date('m/d/Y', strtotime($report->next_send_time)),
                        '%account%' => $ad_account_title,
                        '%balance%' => (string)calculateStripeAmount($total_balance),
                        '%refunds%' => (string)calculateStripeAmount($total_refund_amount),
                        '%disputes%' => (string)calculateStripeAmount($total_dispute_amount),
                        '%customers%' => (string)$total_customers,
                        '%charges%' => (string)calculateStripeAmount($total_charge_amount),
                        '%payouts%' => (string)calculateStripeAmount($total_payout_amount),
                        '%stripe_customers_html%' => (string)$stripe_customers_html,
                        '%stripe_transactions_html%' => (string)$stripe_transactions_html,
                        '%logo_property%' => $logo,
                    ];
                    if ($report->attachment_type == 'pdf') {
                        sendMail($email, $report->email_subject, '469d4ce7-6c32-4c51-a9dd-c11d2a416eaa', $stripe_email_substitutions, $attachments);
                        if (file_exists($pdf_dir . $pdf_file_name)) {
                            unlink($pdf_dir . $pdf_file_name);
                        }
                    } else {
                        sendMail($email, $report->email_subject, '469d4ce7-6c32-4c51-a9dd-c11d2a416eaa', $stripe_email_substitutions);
                    }
                    Schedule::create([
                        'user_id' => $report->user_id,
                        'report_id' => $report->id,
                        'recipient' => $email,
                    ]);
                    $this->sendLimit($report);
                }
            }
        } else {
            session()->flash('alert-danger', 'Report not found.');
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

    private function sendLimit($report)
    {
        $current_plan = $report->user->current_billing_plan ? $report->user->current_billing_plan : 'free_trial';
        $plan = Plan::whereTitle($current_plan)->first();
        $reports_sent_count = Schedule::whereUserId($report->user->id)->whereBetween('created_at', [date('Y-m-01 00:00:00'), date('Y-m-t 00:00:00')])->count();
        if ($reports_sent_count >= $plan->reports && $this->sendLimit == 0) {

            $email = $report->user->email;
            $subject = 'Oh No! You\'ve reached your report limit on Ninja Reports';
            $timestamp = date('Y-m-d');
            $daysLeft = (int)date('t', strtotime($timestamp)) - (int)date('j', strtotime($timestamp));
            $limit_email_substitutions = [
                '%reports%' => (string)$reports_sent_count,
                '%limit%' => (string)$plan->reports,
                '%days_left%' => (string)$daysLeft,
            ];
            sendMail($email, $subject, '99642447-0c92-4931-8038-0c1190f779cd', $limit_email_substitutions);
            $this->sendLimit = 1;
        }
    }

}
