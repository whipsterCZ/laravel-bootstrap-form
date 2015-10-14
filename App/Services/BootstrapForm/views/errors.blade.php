
@if ($errors->any())
	<div class="alert alert-danger">
		<ul>
			@foreach ($errors->getBags('default') as $bagName => $bag)
				@foreach($bag->toArray() as $key => $errors)
					@foreach($errors as $error)
						<li data-bag="{{$bagName}}" data-name="{{$key}}">{{ $error }}</li>
					@endforeach
				@endforeach
			@endforeach
		</ul>
	</div>
@endif