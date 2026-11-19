<option value="">All authors</option>
@foreach ($users as $user)
    <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
        {{ $user->name }} ({{ $authorCounts[$user->id] ?? 0 }})
    </option>
@endforeach
