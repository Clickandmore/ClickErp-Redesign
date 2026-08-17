<style>
    /*.coll td,th{*/
    /*    border-width: 3px !important;*/

    /*}*/
</style>
<div class="row">
{{--    <h3>{{__('rmada.question')}} : {{$item->first()->question->name}}</h3>--}}
    <table class=" table table-bordered border "  >
        <thead>
        <tr>
            <th>{{__('rmada.answers')}}</th>
        </tr>
        </thead>
        <tbody>
        @forelse($item as $answer)
        <tr>

            <td style="background-color: {{$answer->color}};color: #0c0b0b">{{$answer->value}}</td>

        </tr>
        @empty
        @endforelse

        </tbody>
    </table>
</div>
