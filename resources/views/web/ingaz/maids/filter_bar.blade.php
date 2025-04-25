<div class="col-lg-4 p-2">
    <div class="small-filter-header">
        <h6> {{helperTrans('web.advanced_search')}} </h6>
        <button class="openfilter">
            <i class="fa-light fa-sliders"></i>
        </button>
    </div>
    <div class="exploreFilter" id="sideMenu">
        <form action="{{route('web.maids.index')}}" id="filter-submit-global">
            <div class="head">
                <h3>{{helperTrans('web.advanced_search')}}</h3>
                <div class="colse">
                    <i class="fa-light fa-xmark"></i>
                </div>
            </div>
            <div class="deps">
                @foreach($job_types as $job_type)
                <label for="{{$job_type->title.'-'.$job_type->id}}">
                    <h6>{{$job_type->title}}</h6>
                    <input type="radio" value="{{$job_type->id}}" name="job_type_id[]" {{ in_array($job_type->id,
                    (array) old('job_type_id', $filters['job_type_id'] ?? [])) ? 'checked' : '' }}
                    id="{{$job_type->title.'-'.$job_type->id}}">
                </label>
                @endforeach
            </div>
            <div class="field">
                <label for="age"><i class="fa-light fa-hourglass-start"></i> {{helperTrans('web.age')}}</label>
                <select id="age" class="selectpicker" data-style="btn-default">
                    <option value="">{{helperTrans('web.all_ages')}}</option>
                    <option data-age-from="23" data-age-to="30" {{ old('age', $filters['age_from'] ?? '' )=='23' &&
                        old('age_to', $filters['age_to'] ?? '' )=='30' ? 'selected' : '' }}>
                        {{helperTrans('web.from')}} 23 {{helperTrans('web.to')}} 30 {{helperTrans('web.year')}}</option>
                    <option data-age-from="31" data-age-to="36" {{ old('age', $filters['age_from'] ?? '' )=='31' &&
                        old('age_to', $filters['age_to'] ?? '' )=='36' ? 'selected' : '' }}>
                        {{helperTrans('web.from')}} 31 {{helperTrans('web.to')}} 36 {{helperTrans('web.year')}}</option>
                    <option data-age-from="37" data-age-to="44" {{ old('age', $filters['age_from'] ?? '' )=='37' &&
                        old('age_to', $filters['age_to'] ?? '' )=='44' ? 'selected' : '' }}>
                        {{helperTrans('web.from')}} 37 {{helperTrans('web.to')}} 44 {{helperTrans('web.year')}}</option>
                </select>
            </div>
            <input type="hidden" name="age_from" id="age_from"
                value="{{ old('age_from', $filters['age_from'] ?? '') }}">
            <input type="hidden" name="age_to" id="age_to" value="{{ old('age_to', $filters['age_to'] ?? '') }}">
            <div class="field">
                <label for="job"><i class="fa-light fa-briefcase"></i> {{helperTrans('web.occupation')}}</label>
                <select id="job" name="job_title_id" class="selectpicker" data-style="btn-default">
                    <option value="">{{helperTrans('web.all_jobs')}}</option>
                    @foreach($job_titles as $job_title)
                    <option value="{{$job_title->id}}" {{ old('job_title_id', $filters['job_title_id'] ?? ''
                        )==$job_title->id ? 'selected' : '' }} >
                        {{$job_title->title}}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="nationality"><i class="fa-sharp fa-regular fa-flag"></i>
                    {{helperTrans('web.nationality')}}</label>
                <select id="nationality" name="nationality_id" class="selectpicker" data-style="btn-default">
                    <option value="">{{helperTrans('web.all_nationalities')}}</option>
                    @foreach($nationalities as $nationality)
                    <option value="{{$nationality->id}}" {{ old('nationality_id', $filters['nationality_id'] ?? ''
                        )==$nationality->id ? 'selected' : '' }} >
                        {{$nationality->sub_title}}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="religion"><i class="fa-sharp fa-person-praying"></i>
                    {{helperTrans('web.religions')}}</label>
                <select id="religion" name="religion_id" class="selectpicker" data-style="btn-default">
                    <option value="">{{helperTrans('web.all_religions')}}</option>
                    @foreach($religions as $religion)
                    <option value="{{$religion->id}}" {{ old('religion_id', $filters['religion_id'] ?? '' )==$religion->
                        id ? 'selected' : '' }} >
                        {{$religion->title}}</option>
                    @endforeach
                </select>
            </div>
            <button class="apply" type="submit" id="closeFilter">
                {{helperTrans('web.apply')}}
            </button>
        </form>
    </div>
</div>
<script>
    let openFilter = document.querySelector('.openfilter');
    let sideMenu = document.getElementById("sideMenu");
    let close = document.querySelector(".colse");
    let submit = document.getElementById("closeFilter")
    openFilter.addEventListener('click', () => {
        sideMenu.classList.add("active");
    })
    close.addEventListener('click', () => {
        sideMenu.classList.remove("active");
    })
    submit.addEventListener('click', () => {
        sideMenu.classList.remove("active");
    })
</script>
