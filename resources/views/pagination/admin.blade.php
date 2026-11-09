@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="admin-table-pagination-bar" style="padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; font-weight: 600;">
                <span>Show</span>
                @php $currentPerPage = (int)request('per_page', 15); @endphp
                <select onchange="const u = new URL(window.location.href); u.searchParams.set('per_page', this.value); u.searchParams.delete('page'); window.location.href = u.toString();"
                        style="padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: 700; background: #fff; color: #0f172a; cursor: pointer; outline: none;">
                    <option value="10" {{ $currentPerPage == 10 ? 'selected' : '' }}>10</option>
                    <option value="15" {{ $currentPerPage == 15 ? 'selected' : '' }}>15</option>
                    <option value="25" {{ $currentPerPage == 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ $currentPerPage == 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ $currentPerPage == 100 ? 'selected' : '' }}>100</option>
                </select>
                <span>entries</span>
            </div>
            <span style="color: #cbd5e1; font-weight: 300;">|</span>
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                @if ($paginator->total() > 0)
                    Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
                @else
                    No rows to display
                @endif
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 6px;">
            @if ($paginator->onFirstPage())
                <span class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; opacity: 0.45; pointer-events: none;">&laquo; Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; text-decoration: none;">&laquo; Previous</a>
            @endif

            <div style="display: inline-flex; gap: 4px; align-items: center;">
                @if(isset($elements))
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span style="padding: 4px 6px; font-size: 12px; color: #94a3b8; font-weight: 700;">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span style="min-width: 32px; height: 32px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #0284c7; background: #0284c7; color: #ffffff;">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" style="min-width: 32px; height: 32px; padding: 0 8px; border-radius: 6px; font-size: 12px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; text-decoration: none;">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                @endif
            </div>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; text-decoration: none;">Next &raquo;</a>
            @else
                <span class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; opacity: 0.45; pointer-events: none;">Next &raquo;</span>
            @endif
        </div>
    </div>
@endif
