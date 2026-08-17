@php
    $currentSection = null;
    $skipFirstSectionHeading = $suppressFirstHeading ?? false;
@endphp

@foreach($terms as $term)
    @php
        $sectionTitle = data_get($term, 'section_title');
        $termNumber = data_get($term, 'term_number');
        $termContent = trim((string) data_get($term, 'term_content', ''));
    @endphp

    @continue(\Illuminate\Support\Str::startsWith($termContent, 'وعلى ذلك جرى التوقيع'))

    @if(filled($sectionTitle) && $currentSection !== $sectionTitle)
        @if($skipFirstSectionHeading)
            @php
                $currentSection = $sectionTitle;
                $skipFirstSectionHeading = false;
            @endphp
        @else
        <h3>{{ $sectionTitle }}</h3>
        @php $currentSection = $sectionTitle; @endphp
        @endif
    @endif

    <div class="term-item">
        @if(filled($termNumber))
            <span class="term-number">{{ $termNumber }}.</span>
        @endif
        {!! $formatRentalContractTerm($term) !!}
    </div>
@endforeach
