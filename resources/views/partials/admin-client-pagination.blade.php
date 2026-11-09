@props(['prefix' => 'table', 'defaultPerPage' => 15])
<div class="admin-table-pagination-bar" style="padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #64748b; font-weight: 600;">
            <span>Show</span>
            <select id="{{ $prefix }}PageSize" class="filter-select admin-page-size-select" style="padding: 5px 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 12px; font-weight: 700; background: #fff; color: #0f172a; cursor: pointer; outline: none;">
                <option value="10" {{ $defaultPerPage == 10 ? 'selected' : '' }}>10</option>
                <option value="15" {{ $defaultPerPage == 15 ? 'selected' : '' }}>15</option>
                <option value="25" {{ $defaultPerPage == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ $defaultPerPage == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ $defaultPerPage == 100 ? 'selected' : '' }}>100</option>
            </select>
            <span>entries</span>
        </div>
        <span style="color: #cbd5e1; font-weight: 300;">|</span>
        <span id="{{ $prefix }}PageInfo" style="font-size: 13px; color: #64748b; font-weight: 600;">—</span>
    </div>
    <div style="display: flex; align-items: center; gap: 6px;">
        <button type="button" id="{{ $prefix }}PagePrev" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer;">&laquo; Previous</button>
        <div id="{{ $prefix }}PageNumbers" style="display: inline-flex; gap: 4px; align-items: center;"></div>
        <button type="button" id="{{ $prefix }}PageNext" class="btn btn-outline" style="padding: 6px 12px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; color: #475569; cursor: pointer;">Next &raquo;</button>
    </div>
</div>
