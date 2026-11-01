<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    @php $conversation = $conversationMessage->conversation; @endphp
    <h2>پیام جدید از مشتری</h2>

    <p><strong>موضوع:</strong> {{ $conversation->subject }}</p>
    <p><strong>مشتری:</strong> {{ $conversation->customer->name }} — <span dir="ltr">{{ $conversation->customer->phone }}</span></p>

    <p style="white-space: pre-line; background: #f5f5f5; padding: 12px;">{{ $conversationMessage->body }}</p>

    @if ($conversationMessage->attachments->isNotEmpty())
        <p>{{ $conversationMessage->attachments->count() }} فایل پیوست — در پنل مدیریت قابل مشاهده است.</p>
    @endif

    <p><a href="{{ route('admin.conversations.show', $conversation) }}">پاسخ در پنل مدیریت</a></p>
</body>
</html>
