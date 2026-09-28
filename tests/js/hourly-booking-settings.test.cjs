const {test}=require('node:test');
const assert=require('node:assert/strict');
const vm=require('node:vm');
const {readFileSync}=require('node:fs');
const source=readFileSync('public/admin/assets/js/hourly-booking.js','utf8');

function harness(state){
    const nodes={};
    const element=()=>({children:[],value:'',hidden:false,innerHTML:'',textContent:'',
        append(...items){this.children.push(...items);},appendChild(item){this.children.push(item);},
        replaceChildren(){this.children=[];},add(item){this.children.push(item);},querySelectorAll(){return [];}});
    const $=id=>nodes[id]??=(Object.assign(element(),{id}));
    const context={state,$,document:{createElement:element},Option:class{constructor(label,value){this.label=label;this.value=value;}},
        money:amount=>String(amount),esc:String,busy:false,month:new Date(2030,0,1),selectedDate:'',
        refreshQuote:async()=>{context.refreshed=true;},fail:e=>{throw e;}};
    vm.createContext(context);
    for(const name of ['renderServiceOptions','renderCalendar','summary','context']){
        const start=source.indexOf('    function '+name+'('),end=source.indexOf('\n    }',start)+6;
        vm.runInContext(source.slice(start,end),context);
    }
    return context;
}

test('configured fields and stepper labels/order reach the form and submitted payload',()=>{
    const h=harness({item:{booking_config:{auto_assign:true,steppers:[
        {key:'quantity',label:'Rooms',min:2,max:4},{key:'workers',label:'Workers',min:1,max:3}],
        fields:{bring_materials:{type:'boolean',label:'Materials',default:false},service_type:{type:'select',label:'Type',default:'home',options:[{label:'Home',value:'home'},{label:'Company',value:'company'}]}},notes:[{text:'Working days'}]}},
        quantity:2,workers:1,options:{},customer_mode:'existing',address_mode:'existing'});
    h.renderServiceOptions();
    assert.equal(h.$('booking-quantity').min,2);assert.equal(h.$('booking-quantity').max,4);
    assert.equal(h.$('booking-quantity-label').textContent,'Rooms');assert.equal(h.$('booking-auto-assign').hidden,false);
    assert.deepEqual(h.$('booking-count-fields').children.map(n=>n.id),['booking-quantity-field','booking-workers-field']);
    const materials=h.$('booking-service-options').children[0].children[1];
    materials.checked=true;materials.onchange();
    const type=h.$('booking-service-options').children[1].children[1];type.value='company';type.onchange();
    assert.equal(h.context().bring_materials,true);assert.equal(h.context().service_type,'company');assert.equal(h.context().quantity,2);
    assert.equal(h.refreshed,true);
    h.state.item.booking_config={};h.state.options={};h.renderServiceOptions();
    assert.equal(h.$('booking-service-options').children.length,0);assert.equal(h.$('booking-quantity-field').hidden,true);
});

test('price summary follows returned fee and tax rows without adding included tax twice',()=>{
    const h=harness({step:4,visits:[],quote:{total:11500,price_display:'total',presentation:{lines:[{label:'Service',amount:10000},{label:'Transport',amount:1500}],tax_line:{label:'Included VAT',amount:1500}}}});
    h.summary();
    assert.match(h.$('price-summary').innerHTML,/Transport/);assert.match(h.$('price-summary').innerHTML,/Included VAT/);
    assert.match(h.$('price-summary').innerHTML,/class="total">115</);
    h.state.quote.presentation={lines:[{label:'Service',amount:11500}],tax_line:null};h.summary();
    assert.doesNotMatch(h.$('price-summary').innerHTML,/Transport|Included VAT/);
    assert.match(h.$('price-summary').innerHTML,/class="total">115</);
});

test('calendar respects server date limits, working days and multiple-session policy',()=>{
    const h=harness({item:{booking_config:{booking:{min_date:'2030-01-04',max_date:'2030-01-15',working_days:{3:{is_on:false}},allow_multiple_sessions_per_day:false}}},
        visits:[{starts_at:''},{starts_at:'2030-01-07 09:00'}]});
    h.$('visit-index').value='0';h.renderCalendar();
    const button=date=>h.$('booking-calendar').innerHTML.match(new RegExp('<button[^>]*data-date="'+date+'"[^>]*>'))[0];
    assert.doesNotMatch(button('2030-01-04'),/disabled/);
    for(const date of ['2030-01-03','2030-01-16','2030-01-09','2030-01-07'])assert.match(button(date),/disabled/);
    h.state.item.booking_config.ignore_closed_days=true;h.state.item.booking_config.booking.allow_multiple_sessions_per_day=true;h.renderCalendar();
    assert.doesNotMatch(button('2030-01-09'),/disabled/);assert.doesNotMatch(button('2030-01-07'),/disabled/);
});
