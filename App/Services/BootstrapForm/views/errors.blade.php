@if ($errors->getBag($errorBag)->count())
	<div class="alert alert-danger">
		<ul>
			@foreach($errors->getBag($errorBag)->toArray() as $key => $errors)
				@foreach($errors as $error)
					<li data-name="{{$key}}">{{ $error }}</li>
				@endforeach
			@endforeach
		</ul>
	</div>
@endif