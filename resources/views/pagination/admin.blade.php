@if ($paginator->hasPages() || $paginator->total() > 0)
    <div style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
        <div style="font-size: 13px; color: #64748b; font-weight: 600;">
            @if ($paginator->total() > 0)
                Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
            @else
                No rows to display
            @endif
        </div>
        @if ($paginator->hasPages())
            <div style="display: flex; gap: 8px;">
                @if ($paginator->onFirstPage())
                    <span class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; opacity: 0.45; pointer-events: none;">Previous</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; text-decoration: none;">Previous</a>
                @endif
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; text-decoration: none;">Next</a>
                @else
                    <span class="btn btn-outline" style="padding: 6px 14px; font-size: 13px; opacity: 0.45; pointer-events: none;">Next</span>
                @endif
            </div>
        @endif
    </div>
@endif
