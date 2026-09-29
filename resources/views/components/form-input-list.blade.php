<div class="{{ $divClass }}" style="{{ $style }}">
    <div class="form-group mb-3">
        <label for="{{ $forLabel }}">{{ $inputLabel }}</label>
        <input type="{{ $type }}" class="form-control" id="{{ $id }}" placeholder="{{ $placeHolder }}"
            list="{{ $list }}" oninput="{{ $oninput }}" fdprocessedid="3s3mk" name="{{ $name }}"
            value="{{ $value }}" {{ $readonly ? 'readonly' : '' }}>
    </div>
    <datalist id={{ $list }}>
        {{ $slot }}
    </datalist>
    <x-form-error name='{{ $name }}' />
</div>
