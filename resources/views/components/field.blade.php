@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'id' => null])
@php $fieldId=$id??$name; @endphp
<div class="field">
 <label for="{{ $fieldId }}">{{ $label }} @if($required)<span class="required" aria-hidden="true">*</span>@endif</label>
 <input id="{{ $fieldId }}" name="{{ $name }}" type="{{ $type }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required) @if($hint || $errors->has($name)) aria-describedby="{{ $fieldId }}-help" @endif @if($errors->has($name)) aria-invalid="true" @endif {{ $attributes }}>
 @if($errors->has($name))<small class="error" id="{{ $fieldId }}-help">{{ $errors->first($name) }}</small>@elseif($hint)<small id="{{ $fieldId }}-help">{{ $hint }}</small>@endif
</div>
