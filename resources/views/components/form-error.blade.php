@props(['name'])

@error($name)
    <p class="text-danger" style="font-size: 10px;">{{ $message }}</p>
@enderror
