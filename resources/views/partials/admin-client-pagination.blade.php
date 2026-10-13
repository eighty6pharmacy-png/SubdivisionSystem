@props(['prefix' => 'table'])
<div style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
    <span id="{{ $prefix }}PageInfo" style="font-size: 13px; color: #64748b; font-weight: 600;">—</span>
    <div style="display: flex; gap: 8px;">
        <button type="button" id="{{ $prefix }}PagePrev" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px;">Previous</button>
        <button type="button" id="{{ $prefix }}PageNext" class="btn btn-outline" style="padding: 6px 14px; font-size: 13px;">Next</button>
    </div>
</div>
