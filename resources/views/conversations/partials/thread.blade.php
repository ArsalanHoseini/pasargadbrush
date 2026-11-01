{{--
    A conversation's messages, oldest first. The viewer's own messages sit
    on one side, the other party's on the other.
    @param \App\Models\Conversation $conversation  (messages.attachments, messages.user loaded)
    @param string $viewer  'customer' | 'staff'
    @param string $attachmentRoute  Route name serving an attachment to this viewer.
--}}
<ol class="space-y-4" aria-label="پیام‌ها">
    @foreach ($conversation->messages as $message)
        @php $isOwn = $message->author_type === $viewer; @endphp
        <li @class(['flex', 'justify-start' => $isOwn, 'justify-end' => ! $isOwn])>
            <div @class([
                'max-w-[85%] rounded-lg border p-4 text-sm',
                'border-neutral-200 bg-white' => $isOwn,
                'border-amber-200 bg-amber-50' => ! $isOwn,
            ])>
                <p class="mb-2 flex flex-wrap items-baseline justify-between gap-3 text-xs text-neutral-500">
                    <span class="font-medium text-neutral-700">
                        @if ($message->isFromStaff())
                            {{ $viewer === 'staff' ? 'کارشناس — '.($message->user?->name ?? 'نامشخص') : 'کارشناس '.setting('site_name', 'پاسارگاد برس') }}
                        @else
                            {{ $viewer === 'staff' ? 'مشتری — '.$conversation->customer->name : 'شما' }}
                        @endif
                    </span>
                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ \App\Support\PersianDate::format($message->created_at) }}</time>
                </p>
                <p class="whitespace-pre-line text-neutral-900">{{ $message->body }}</p>

                @if ($message->attachments->isNotEmpty())
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach ($message->attachments as $attachment)
                            <li>
                                <a href="{{ route($attachmentRoute, $attachment) }}" target="_blank" rel="noopener"
                                   class="flex items-center gap-2 rounded border border-neutral-200 bg-white px-2 py-1 text-xs hover:border-neutral-400">
                                    @if ($attachment->isImage())
                                        <img src="{{ route($attachmentRoute, $attachment) }}" alt="" class="h-10 w-10 rounded object-cover" loading="lazy">
                                    @else
                                        <span class="rounded bg-red-50 px-1.5 py-0.5 font-bold text-red-700">PDF</span>
                                    @endif
                                    <span class="max-w-40 truncate" dir="ltr">{{ $attachment->original_name }}</span>
                                    <span class="text-neutral-400">{{ number_format($attachment->size / 1024) }} KB</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </li>
    @endforeach
</ol>
