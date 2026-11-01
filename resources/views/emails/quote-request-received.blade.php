<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
</head>
<body style="font-family: Tahoma, sans-serif; font-size: 14px; color: #1d1d1d;">
    <h2>درخواست استعلام قیمت جدید</h2>

    @if ($quoteRequest->product)
        <p><strong>محصول:</strong> {{ $quoteRequest->product->name }}</p>
    @else
        <p><strong>محصول:</strong> درخواست عمومی (بدون محصول مشخص)</p>
    @endif

    <p><strong>نام:</strong> {{ $quoteRequest->name }}</p>
    <p><strong>تلفن:</strong> {{ $quoteRequest->phone }}</p>
    @if ($quoteRequest->email)
        <p><strong>ایمیل:</strong> {{ $quoteRequest->email }}</p>
    @endif
    @if ($quoteRequest->company)
        <p><strong>شرکت:</strong> {{ $quoteRequest->company }}</p>
    @endif
    @if ($quoteRequest->requested_quantity)
        <p><strong>تیراژ/تعداد درخواستی:</strong> {{ $quoteRequest->requested_quantity }}</p>
    @endif

    @if (! empty($quoteRequest->customization['dimensions']))
        <p><strong>ابعاد درخواستی:</strong></p>
        <ul>
            @foreach ($quoteRequest->customization['dimensions'] as $dimension)
                <li>{{ $dimension['code'] }} — {{ $dimension['label'] }}: {{ $dimension['value'] }} {{ \App\Models\ProductDimensionField::UNITS[$dimension['unit']] ?? $dimension['unit'] }}</li>
            @endforeach
        </ul>
    @endif

    <p><strong>پیام:</strong></p>
    <p>{{ $quoteRequest->message }}</p>

    <hr>
    <p style="color: #888;">
        این پیام از طریق فرم درخواست استعلام قیمت سایت پاسارگاد برس ارسال شده و در پنل مدیریت هم قابل مشاهده است.
    </p>
</body>
</html>
