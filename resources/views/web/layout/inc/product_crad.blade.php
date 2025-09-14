<div class="product_crad">
    <div class="product_image">
        <img src="{{showFile($product->main_image)}}" alt="product">
        <span>أوريون</span>
        <button data-id="{{ $product->id }}"
                id="add_to_wishlist{{$product->id}}" data-quantity="1" class="add_to_wishlist {{$product->is_favourite?'active' : ''}}" onclick="toggleActiveClass(this)">
            <i class="fa-sharp fa-light fa-heart"></i>
        </button>
    </div>
    <div class="product_info">
        <h5 class="pro_name" title="{{$product->title}}">
            <a href="{{route('web.products.show',$product->id)}}">{{$product->title}}</a>
        </h5>
        <p class="pro_number">NO: {{$product->code}}</p>
        <p class="trader">{{$product->users->name}} </p>
        <div class="price_buy">

            <h6>{{$product->final_price}} {{ @$settings->currency  ? $settings->currency : __('web.sar') }}</h6>
            <button class="add_to_cart" id="add_to_cart{{$product->id}}" data-product-quantity="1" data-quantity="1"
                    data-product-id="{{$product->id}}">
                <i class="fa-regular fa-cart-shopping-fast"></i>
            </button>
        </div>
        <div class="rate_sale">

            <p>
                @if($product->active_offer)
                    <span class="old_price">{{$product->price }} {{ @$settings->currency  ? $settings->currency : __('web.sar') }}</span>
                    <span class="sale"> {{__('web.discount')}}  {{$product->active_offer->value}} {{$product->active_offer->type == \App\Enums\ProductOfferType::Value->value? @$settings->currency  ? $settings->currency : __('web.sar') :"%" }} </span>
                @endif
            </p>

            <a href="#!">
                <b>{{$product->rate }} <i class="fa-sharp fa-solid fa-star"></i></b> ({{$product->products_rates->count() }})
            </a>
        </div>
    </div>
</div>
