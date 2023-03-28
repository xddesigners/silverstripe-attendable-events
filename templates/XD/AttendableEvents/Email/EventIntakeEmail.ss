<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width"/>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>$Subject</title>
</head>

<body itemscope itemtype="http://schema.org/EmailMessage" style="word-break:break-word;margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;-webkit-font-smoothing: antialiased;-webkit-text-size-adjust: none;height: 100%;font-size: 14px;line-height: 1.6em;background-color: #f6f6f6;width: 100% !important;">

<table class="body-wrap" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;background-color: #f6f6f6;width: 100%;">
    <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
        <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;"></td>
        <td class="container" width="600" style="margin: 0 auto !important;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;display: block !important;max-width: 600px !important;clear: both !important;">
            <div class="content" style="margin: 0 auto;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;max-width: 600px;display: block;padding: 20px;">
                <table class="main" width="100%" cellpadding="0" cellspacing="0" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;background-color: #fff;border: 1px solid #e9e9e9;border-radius: 3px;">
                    <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                        <td class="content-wrap aligncenter" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 20px;text-align: center;">
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                    <td class="content-block title-block" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 20px;text-align: center;"  colspan="2">
                                        <a href="$BaseURL" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;color: #002e67;text-decoration: underline;">
                                            <img src="{$AbsoluteBaseUrl}/app/images/mail-logo.png" alt="$SiteConfig.Title" width="135" height="127" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;max-width: 100%;">
                                        </a>
                                    </td>
                                </tr>

                                <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                    <td class="content-block" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 20px;text-align: left;color:#002e67;" colspan="2">
                                        <h1 style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, &quot;Lucida Grande&quot;, sans-serif;box-sizing:border-box;line-height:1.2em;font-weight:400;font-size:24px;color:#002e67;">$Subject</h1>            
                                        $Body.RAW
                                    </td>
                                </tr>
                                <% if $EventDate %>
                                    <% with $EventDate %>
                                        <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;" colspan="2">
                                                <h3>$Event.Title</h3>
                                            </td>
                                        </tr>
                                        <% if $Event.Host %>
                                        <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;">
                                                Begeleiding:
                                            </td>
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;font-weight:bold;width:70%;">
                                                $Event.Host
                                            </td>
                                        </tr>
                                        <% end_if %>
                                        <% if $AutoAttendeeLimit %>
                                        <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;">
                                                Maximum aantal deelnemers:
                                            </td>
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;font-weight:bold;width:70%;">
                                                $AutoAttendeeLimit
                                            </td>
                                        </tr>
                                        <% end_if %>
                                        <% if $Location %>
                                            <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                                <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;">
                                                    Locatie:
                                                </td>
                                                <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;font-weight:bold;width:70%;">
                                                    <% with $Location %>    
                                                        <strong>$Title</strong><br/>
                                                        <% if $Address %>
                                                            $Address<br/>
                                                        <% end_if %>
                                                        <% if $Postcode || $Suburb %>
                                                            $Postcode $Suburb
                                                        <% end_if %>
                                                    <% end_with %>
                                                </td>
                                            </tr>
                                        <% end_if %>
                                        <% loop $DayDateTimes %>
                                            <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                                <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;">
                                                    <% if $First %>
                                                        Data:
                                                    <% end_if %>
                                                </td>
                                                <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;font-weight:bold;width:70%;">
                                                    <time>$StartDate.Nice</time><% if $StartTime %>,<% end_if %>
                                                    <% if $EndTime %>
                                                        <time>$StartTime.Format('HH:mm')</time>
                                                        &ndash;
                                                        <time>$EndTime.Format('HH:mm')</time>
                                                    <% else %>
                                                        <time>$StartTime.Format('HH:mm')</time>
                                                    <% end_if %>
                                                </td>
                                            </tr>
                                        <% end_loop %>
                                    <% end_with %>
                                <% end_if %>

                                <% if $ParsedFields %>
                                    <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                        <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;" colspan="2">
                                            <h3>Jouw informatie</h3>
                                        </td>
                                    </tr>
                                    <% loop $ParsedFields %>
                                        <tr style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;">$Title</td>
                                            <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;padding: 0 0 4px;text-align: left;color:#002e67;font-weight:bold;width:70%;">$Value</td>
                                        </tr>
                                    <% end_loop %>
                                <% end_if %>
                            </table>
                        </td>
                    </tr>
                </table>
                <div class="footer" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;width: 100%;clear: both;color: #999;padding: 20px;">
                    <table width="100%" style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;">
                        
                    </table>
                </div>
            </div>
        </td>
        <td style="margin: 0;font-family: &quot;Helvetica Neue&quot;, Helvetica, Arial, sans-serif;box-sizing: border-box;vertical-align: top;font-size: 14px;"></td>
    </tr>
</table>

</body>
</html>