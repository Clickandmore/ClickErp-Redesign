(function(){
    window.showHourlyDuplicate=function(container,duplicate){
        if(!container || !duplicate?.visits)return;
        const list=document.createElement('div');list.style.marginTop='12px';
        for(const visit of duplicate.visits){
            const row=document.createElement('p'),label=document.createElement('span');
            label.textContent='الطلب '+visit.order_number+' · الزيارة '+visit.visit_number+' · '+visit.starts_at+' ';
            row.appendChild(label);
            const link=document.createElement('a');link.href=visit.add_worker_url||visit.order_url;
            link.textContent=visit.can_add_worker?'نعم، أريد إضافة عاملة لهذه الزيارة':'عرض الزيارة القائمة';
            link.className='btn btn-primary hr-btn';row.appendChild(link);list.appendChild(row);
        }
        const hint=document.createElement('p');hint.textContent='ستراجع تكلفة الزيادة وتوفر العاملة قبل التأكيد. إذا كنت تريد موعدًا مختلفًا، عدّل موعد الحجز الحالي.';list.appendChild(hint);
        container.appendChild(list);
    };
})();