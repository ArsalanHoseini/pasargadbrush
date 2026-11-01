<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    @php $conversation = $conversationMessage->conversation; @endphp
    <h2>پاسخ جدید کارشناس</h2>

    <p>{{ $conversation->customer->name }} عزیز، کارشناس ما درباره‌ی «{{ $conversation->subject }}» به شما پاسخ داد:</p>

    <p style="white-space: pre-line; background: #f5f5f5; padding: 12px;">{{ $conversationMessage->body }}</p>

    @if ($conversationMessage->attachments->isNotEmpty())
        <p>{{ $conversationMessage->attachments->count() }} فایل پیوست — پس از ورود به حساب کاربری قابل دریافت است.</p>
    @endif

    <p><a href="{{ route('account.conversations.show', $conversation) }}">مشاهده و پاسخ در حساب کاربری</a></p>

    <hr>
    <p style="color: #888;">{{ setting('site_name', 'پاسارگاد برس') }}</p>
</body>
</html>
