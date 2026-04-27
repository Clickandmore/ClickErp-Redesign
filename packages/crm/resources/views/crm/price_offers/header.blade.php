<style>
      * {
          box-sizing: border-box;
          padding: 0;
          margin: 0;
          direction: rtl;
          text-align: start;
      }
  
      h1,
      h2,
      h3,
      h4,
      h5,
      h6,
      p,
      span,
      a,
      div {
          margin: 0;
      }
  
      body {
          padding: 12px;
      }
  
  
      header .tnavbar {
          width: 100%;
          padding: 10px 2px;
  
          display: flex;
          align-items: center;
          justify-content: space-between;
      }
  
      header .tnavbar .Name {
          flex: 1;
          display: flex;
          flex-direction: column;
          gap: 12px;
      }
  
      header .tnavbar .Name h2 {
          font-size: 18px;
          text-transform: uppercase;
          font-weight: bold;
          color: #500d0d;
      }
  
      header .tnavbar .Name p {
          font-size: 16px;
          font-weight: 500;
      }
      
      header .tnavbar .En {
          direction: ltr;
      }
  
      header .tnavbar .En * {
          text-align: left;
      }
  
      header .tnavbar .logo img {
          object-fit: contain;
          height: 100px;
          aspect-ratio: 1/1;
      }
  
      header .line {
          border: 1px solid #500d0d;
      }
  
      /*# sourceMappingURL=style.css.map */
</style>
  
<header>
      <div class="tnavbar">
            <div class="Name Ar">
                  <h2> {{ @$print_settings->title_ar }} </h2>
                  <p>رقم الهاتف : {{ @$print_settings->phones }} </p>
                  <p>الرقم الضريبي :  {{ @$print_settings->tax_number }}</p>
            </div>
            <div class="logo">
                  <img src="{{ showFile(@$print_settings->logo) }}" alt="logo" />
            </div>
            <div class="Name En">
                  <h2> {{ @$print_settings->title_en }} </h2>
                  <p> {{ @$print_settings->phones }} : Phone Number </p>
                  <p> {{ @$print_settings->tax_number }} : Tax Number </p>
            </div>
      </div>
</header>
<div>
<hr class="line" />
</div>
