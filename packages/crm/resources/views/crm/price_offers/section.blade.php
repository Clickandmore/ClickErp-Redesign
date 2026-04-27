<section class="contact">
    <div class="contactType">
        <span class="icon">
          <svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg" fill="#000000">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <path fill="#fff"
                    d="M128 224v512a64 64 0 0 0 64 64h640a64 64 0 0 0 64-64V224H128zm0-64h768a64 64 0 0 1 64 64v512a128 128 0 0 1-128 128H192A128 128 0 0 1 64 736V224a64 64 0 0 1 64-64z">
              </path>
              <path fill="#fff"
                    d="M904 224 656.512 506.88a192 192 0 0 1-289.024 0L120 224h784zm-698.944 0 210.56 240.704a128 128 0 0 0 192.704 0L818.944 224H205.056z">
              </path>
            </g>
          </svg>
        </span>
        <p>{{ @$print_settings->email }}</p>
    </div>
    <div class="contactType">
        <span class="icon">
          <svg fill="#fff" viewBox="0 0 24 24" id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg"
               stroke="#fff">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <path
                      d="M20.506,20.454a11.975,11.975,0,0,0,0-16.908c-.009-.01-.011-.022-.021-.031s-.021-.012-.031-.021a11.975,11.975,0,0,0-16.907,0c-.01.009-.023.012-.032.022s-.013.022-.022.032a11.975,11.975,0,0,0,0,16.906c.009.01.012.023.022.032s.022.013.032.022a11.975,11.975,0,0,0,16.907,0c.01-.009.022-.011.031-.021S20.5,20.464,20.506,20.454Zm-18.449-7.4a9.927,9.927,0,0,1,5.275,2.2l-3.07,3.07A9.951,9.951,0,0,1,2.057,13.058Zm2.2-7.382L10.586,12,8.749,13.837a11.921,11.921,0,0,0-6.7-2.784A9.956,9.956,0,0,1,4.262,5.676Zm17.681,5.266a9.935,9.935,0,0,1-5.275-2.2l3.07-3.07A9.951,9.951,0,0,1,21.943,10.942Zm-6.689-3.61a9.927,9.927,0,0,1-2.2-5.275,9.951,9.951,0,0,1,5.266,2.2ZM13.836,8.75,12,10.586,5.676,4.262a9.956,9.956,0,0,1,5.377-2.214A11.933,11.933,0,0,0,13.836,8.75Zm-5.09,7.918a9.927,9.927,0,0,1,2.2,5.275,9.951,9.951,0,0,1-5.266-2.205Zm1.417-1.417L12,13.414l6.324,6.324a9.948,9.948,0,0,1-5.377,2.214A11.921,11.921,0,0,0,10.163,15.251ZM13.414,12l1.836-1.836a11.933,11.933,0,0,0,6.7,2.783,9.956,9.956,0,0,1-2.214,5.377Z">
              </path>
            </g>
          </svg>
        </span>
        <p>{{route('frontend.home')}}</p>
    </div>
    <div class="contactType">
        <span class="icon">
          <svg fill="#fff" version="1.1" id="Icons" xmlns="http://www.w3.org/2000/svg"
               xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 32 32" xml:space="preserve" stroke="#fff">
            <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
            <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
            <g id="SVGRepo_iconCarrier">
              <g>
                <path
                        d="M29,17h-5.4c-1.8,0-3.2-1.5-3.2-3.3c0-0.3,0-0.7-1.1-0.7h-6.6c-1.1,0-1.1,0.4-1.1,0.7c0,1.8-1.4,3.3-3.2,3.3H3 c-0.6,0-1-0.4-1-1c0-5,3.9-9,8.6-9h10.8c4.7,0,8.6,4,8.6,9C30,16.6,29.6,17,29,17z">
                </path>
              </g>
              <path
                      d="M23.6,19c-2.4,0-4.5-1.7-5.1-4h-5.2c-0.6,2.3-2.6,4-5.1,4h-1C6.1,21.4,5,24.4,5,27c0,0.6,0.4,1,1,1h20c0.6,0,1-0.4,1-1 c0-2.6-1.1-5.6-2.3-8H23.6z M16,25c-2.2,0-4-1.8-4-4s1.8-4,4-4s4,1.8,4,4S18.2,25,16,25z">
              </path>
              <path d="M23,30c-0.6,0-1-0.4-1-1v-2c0-0.6,0.4-1,1-1s1,0.4,1,1v2C24,29.6,23.6,30,23,30z"></path>
              <path d="M9,30c-0.6,0-1-0.4-1-1v-2c0-0.6,0.4-1,1-1s1,0.4,1,1v2C10,29.6,9.6,30,9,30z"></path>
            </g>
          </svg>
        </span>
        <p>{{ @$print_settings->phones }}</p>
    </div>
</section>
