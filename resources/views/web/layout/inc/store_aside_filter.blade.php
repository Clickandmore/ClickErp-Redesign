<aside class="col-lg-3 pt-3 pe-lg-3 pe-0 side-menu">
    <div class="filter-wrap">
        <div class="colse">
            <i class="fa-light fa-xmark"></i>
        </div>
        <form id="filter-submit-global" action="{{route('web.stores.show',$store->id)}}" >
            <div class="accordion" id="filterAccordion">
                <!-- categories Level -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingOne">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne" aria-expanded="true"
                                aria-controls="collapseOne">
                            {{__('web.department')}}
                        </button>
                    </h2>
                    <div id="collapseOne" class="accordion-collapse collapse show"
                         aria-labelledby="headingOne" data-bs-parent="#filterAccordion">
                        <div class="accordion-body">
                            <ul>
                                @foreach($departments as $department)
                                    <li>
                                        <label for="department_id_{{$department->id}}" class="field_name">
                                            <img src="{{showFile($department->image)}}" alt="bumber">
                                            <h6>{{$department->title}}</h6>
                                        </label>
                                        <input type="checkbox" class="department_id" value="{{$department->id}}" name="department_id[]" id="department_id_{{$department->id}}">
                                    </li>
                                @endforeach


                            </ul>
                        </div>
                    </div>
                </div>
                <!-- brand level -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingTwo">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseTwo"
                                aria-expanded="false" aria-controls="collapseTwo">
                            {{__('web.brand_model')}}
                        </button>
                    </h2>
                    <div id="collapseTwo" class="accordion-collapse collapse"
                         aria-labelledby="headingTwo" data-bs-parent="#filterAccordion">
                        <div class="accordion-body">
                            <ul>
                                @foreach($brands as $brand)
                                    <li>
                                        <label for="brand_id_{{$brand->id}}" class="field_name">
                                            <img src="{{showFile($brand->image)}}" alt="bumber">
                                            <h6>{{$brand->title}}</h6>
                                        </label>
                                        <input type="checkbox" name="brand_id[]" value="{{$brand->id}}" class="brand_id" id="brand_id_{{$brand->id}}">
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- model level -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingThree">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseThree"
                                aria-expanded="false" aria-controls="collapseThree">
                            {{__('web.model')}}
                        </button>
                    </h2>
                    <div id="collapseThree" class="accordion-collapse collapse"
                         aria-labelledby="headingThree" data-bs-parent="#filterAccordion">
                        <div class="accordion-body">
                            <ul>

                                @foreach($cars as $car)
                                    <li>
                                        <label for="car_id_{{$car->id}}" class="field_name">
                                            <h6>{{$car->title}}</h6>
                                        </label>
                                        <input type="checkbox" value="{{$car->id}}" name="car_id[]" id="car_id_{{$car->id}}">
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <!-- manufacturing year -->
                <div class="accordion-item">
                    <h2 class="accordion-header" id="headingFour">
                        <button class="accordion-button collapsed" type="button"
                                data-bs-toggle="collapse" data-bs-target="#collapseFour"
                                aria-expanded="false" aria-controls="collapseFour">
                            {{__('web.manufacture_year')}}
                        </button>
                    </h2>
                    <div id="collapseFour" class="accordion-collapse collapse"
                         aria-labelledby="headingFour" data-bs-parent="#filterAccordion">
                        <div class="accordion-body">
                            <ul>
                                @foreach($years as $year)
                                    <li>
                                        <label for="year{{$year->year}}" class="field_name">
                                            <h6>{{$year->year}}</h6>
                                        </label>
                                        <input value="{{$year->year}}" type="checkbox" name="manufacture_year[]" id="year{{$year->year}}">
                                    </li>
                                @endforeach


                            </ul>
                        </div>
                    </div>
                </div>
                <input type="hidden" id="store_id_search" name="store_id" value="{{$store->id}}">
            </div>
            <button type="submit" class="save_filter">
                {{__('web.confirm')}}
            </button>
        </form>
    </div>
</aside>
