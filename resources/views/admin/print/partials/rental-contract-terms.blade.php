@php
    $currentSection = null;
@endphp

@foreach($terms as $term)
    @php
        $sectionTitle = data_get($term, 'section_title');
        $termNumber = data_get($term, 'term_number');
    @endphp

    @if($currentSection !== $sectionTitle)
        <h3>{{ $sectionTitle }}</h3>
        @php $currentSection = $sectionTitle; @endphp
    @endif

    <div class="term-item">
        @if(filled($termNumber))
            <span class="term-number">{{ $termNumber }}-</span>
        @endif
        {!! $formatRentalContractTerm($term) !!}
    </div>
@endforeach
