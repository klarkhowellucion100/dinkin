@props(['status'])

<style>
    .status-box {
        margin-bottom: 15px;
        padding: 10px 12px;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 500;
        color: #22c55e;
        background: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.3);
        backdrop-filter: blur(8px);
    }
</style>

@if ($status)
    <div {{ $attributes->merge(['class' => 'status-box']) }}>
        {{ $status }}
    </div>
@endif
