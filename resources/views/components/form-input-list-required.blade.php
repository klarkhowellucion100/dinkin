<div class="{{ $divClass }}" style="{{ $style }}">
    <div class="form-floating mb-3">
        <input type="{{ $type }}" class="form-control" id="{{ $id }}" placeholder="{{ $placeHolder }}"
            list="{{ $list }}" required oninput="{{ $oninput }}" fdprocessedid="3s3mk"
            name="{{ $name }}" value="{{ $value }}" {{ $readonly ? 'readonly' : '' }}>
        <label for="{{ $forLabel }}">{{ $inputLabel }}</label>
    </div>
    <datalist id={{ $list }}>
        {{ $slot }}
    </datalist>
    <x-form-error name='{{ $name }}' />
</div>
