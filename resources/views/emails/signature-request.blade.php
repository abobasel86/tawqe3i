<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلب توقيع</title>
</head>
<body style="font-family: system-ui, sans-serif; text-align: right; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; border: 1px solid #ddd; padding: 30px; border-radius: 8px;">
        <h1 style="color: #333;">مرحباً {{ $participant->name }},</h1>
        <p style="font-size: 16px; color: #555; line-height: 1.6;">
            لقد تم دعوتك من قبل {{ $document->user->name }} لتوقيع المستند المعنون بـ "<strong>{{ $document->name }}</strong>".
        </p>
        <p style="font-size: 16px; color: #555; line-height: 1.6;">
            يرجى الضغط على الزر أدناه لمراجعة المستند والتوقيع عليه.
        </p>
        <div style="text-align: center; margin: 30px 0;">
            <a href="{{-- route('sign.page', $participant->token) --}}" style="background-color: #156b68; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 18px;">
                مراجعة وتوقيع المستند
            </a>
        </div>
        <hr style="border: none; border-top: 1px solid #ddd; margin: 30px 0;">
        <p style="font-size: 12px; color: #999; text-align: center;">
            تم إرسال هذه الرسالة من خلال منصة توقيعي.
        </p>
    </div>
</body>
</html>