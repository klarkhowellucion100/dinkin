<div class="mt-3 mb-3">
    <form action="{{ $formAction }}" method="POST">
        @csrf
        @method('POST')
        <div class="input-group w-50">

            <input type="text" class="form-control" placeholder="Search" aria-label="Search" name="search"
                aria-describedby="search-button">
            <button class="btn btn-outline-secondary" type="submit" id="search-button">Search</button>
        </div>
    </form>
</div>
