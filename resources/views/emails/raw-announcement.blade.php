@php
    // {photo} becomes the recipient's inline-embedded photo (cid attachment —
    // remote URLs are blocked or unreachable in most mail clients).
    $photoTag = '';

    if ($embedPhotoPath) {
        $photoTag = '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:0 0 20px;"><tr><td align="center">'
            .'<img src="'.$message->embed($embedPhotoPath).'" alt="" width="140" style="display:block;width:140px;height:140px;object-fit:cover;border-radius:70px;border:4px solid #F9EDF1;">'
            .'</td></tr></table>';
    }
@endphp
{!! str_replace('{photo}', $photoTag, $htmlBody) !!}
