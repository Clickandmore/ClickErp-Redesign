<p>هناك <span>{{ Cart::instance('shopping')->count() }}</span> {{__('web.Item_in_your_shopping_car')}}</p>
<div class="inner_cart ">

    @if (Cart::instance('shopping')->count()>0)
    @foreach (Cart::instance('shopping')->content() as $item)
        <div class="product">
            <div class="img">
                <img src="{{ showFile($item->model->main_image) }}" alt="product">
            </div>
            <div class="info">
                <h6>
                    <a href="{{route('web.products.show',$item->id)}}">{{$item->name}}</a>
                </h6>
                <p>{{ __('web.PRICE') }} :<span>{{$item->subtotal()}} {{ @$settings->currency  ? $settings->currency : __('web.sar') }}</span></p>
                <div class="count_delete">
                    <p>{{__('web.quantity')}} : <span>X{{$item->qty}}</span></p>
                    <button  class="cart_delete" data-id="{{$item->rowId}}">
                        <i class="fa-regular fa-trash-can-list"></i>
                    </button>
                </div>
            </div>
        </div>

        @endforeach
    @else
        <img src="{{asset('web')}}/assets/images/empty-cart.png" alt="">
    @endif
    <div class="subtotal">
        <p> {{ __('web.TOTAL') }}:</p>
        <span>{{ @$settings->currency  ? $settings->currency :  __('web.sar')  }}{{Cart::subtotal()}} </span>
    </div>
</div>
<div class="btns">
    <a href="{{route('web.shoppingCart')}}">{{__('web.shopping_cart')}}</a>
    <a href="{{route('web.checkout')}}">{{__('web.Checkout')}}</a>
</div>
