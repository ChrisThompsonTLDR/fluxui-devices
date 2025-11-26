{{-- Stream targets are only set for assistant messages --}}
@if($messageObj->role === 'assistant')
    <div 
        wire:stream="{{ $streamTarget }}"
        id="{{ $streamTarget }}"
    >
    </div>
@endif

{{-- Skeleton loading UI should only display for assistant messages --}}
@if($messageObj->streaming && empty($messageObj->content) && $messageObj->role === 'assistant')
    <div class="skeleton-loading">
        {{-- Skeleton UI content --}}
    </div>
@endif
