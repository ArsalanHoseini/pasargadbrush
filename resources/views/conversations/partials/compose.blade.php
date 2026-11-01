{{--
    Message body + attachments (enctype set on the enclosing form).
    @param string $placeholder
--}}
<div class="space-y-3">
    <div>
        <label for="body" class="sr-only">متن پیام</label>
        <textarea id="body" name="body" rows="5" required placeholder="{{ $placeholder ?? 'پیام خود را بنویسید…' }}"
                  class="w-full rounded border border-neutral-300 px-3 py-2 text-sm">{{ old('body') }}</textarea>
    </div>
    <div>
        <label for="attachments" class="mb-1 block text-xs text-neutral-600">
            فایل پیوست (اختیاری — تا {{ \App\Services\Messaging\ConversationService::MAX_ATTACHMENTS }} فایل عکس یا PDF، هر کدام حداکثر ۱۰ مگابایت)
        </label>
        <input id="attachments" type="file" name="attachments[]" multiple accept="image/jpeg,image/png,image/webp,application/pdf"
               class="block w-full text-sm file:me-3 file:rounded file:border-0 file:bg-neutral-900 file:px-3 file:py-1.5 file:text-xs file:text-white">
    </div>
</div>
