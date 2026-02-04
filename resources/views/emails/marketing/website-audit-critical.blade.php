@php
    $subject = 'Critical website audit issues detected';
    $preheader = 'New critical issues found for ' . ($websiteUrl ?? 'your website');
@endphp

@component('emails._layout', ['subject' => $subject, 'preheader' => $preheader])
    <div style="font-size:18px;font-weight:700;margin-bottom:12px;color:#0f172a;">
        New critical website audit issues
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:16px;">
        <tr>
            <td style="padding:14px 16px;background:#f8fafc;">
                <div style="font-size:12px;color:#64748b;text-transform:uppercase;letter-spacing:.04em;">Audit Details</div>
            </td>
        </tr>
        <tr>
            <td style="padding:14px 16px;">
                <div style="font-size:13px;color:#334155;line-height:1.6;">
                    <strong>Website:</strong> {{ $websiteUrl }}<br>
                    <strong>Audit ID:</strong> #{{ $auditId }}
                    @if(!empty($scores))
                        <br><strong>Overall score:</strong> {{ $scores['overall'] ?? '—' }}
                        <br><span style="color:#64748b;font-size:12px;">SEO {{ $scores['seo'] ?? '—' }} · Performance {{ $scores['performance'] ?? '—' }} · Accessibility {{ $scores['accessibility'] ?? '—' }}</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div style="font-size:15px;font-weight:600;margin:20px 0 12px 0;color:#0f172a;">
        New critical issues
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #ef444410;border-radius:12px;overflow:hidden;background:#fef2f2;margin-bottom:16px;">
        <tr>
            <td style="padding:14px 16px;">
                @foreach((array) $issues as $i)
                    <div style="margin-bottom:12px;@if(!$loop->last) padding-bottom:12px;border-bottom:1px solid #fee2e2; @endif">
                        <div style="font-size:13px;font-weight:600;color:#991b1b;margin-bottom:4px;">
                            {{ $i['category'] ?? 'general' }} · {{ $i['issue_type'] ?? 'issue' }}
                        </div>
                        <div style="font-size:13px;color:#334155;line-height:1.5;">
                            {{ $i['description'] ?? '' }}
                        </div>
                        @if(!empty($i['affected_url']))
                            <div style="font-size:12px;color:#64748b;margin-top:4px;">
                                Affected: {{ $i['affected_url'] }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <div style="font-size:13px;color:#64748b;line-height:1.6;margin-top:20px;">
        This alert is sent when newly-detected critical issues appear compared to the previous completed audit.
    </div>
@endcomponent

