<div class="panel panel-default">
    <div class="panel-heading">
    @lang('messages.pictures')
    </div>
    <div class="panel-body">
    <table class="picture-table">
<tr>
<?php $i = 0; ?>
@foreach ($pictures as $picture)
<?php if ($i++ == 4) { echo "</tr><tr>"; $i = 1; } ?>
<td>
<?php $col = $picture->collectors;?>
    <span class="picture-credits">
@if ($col->count() == 1)
<a href="{{url('persons/' . $col[0]->person_id)}}">{{$col[0]->person->abbreviation}}</a>
@elseif ($col->count() == 2)
<a href="{{url('persons/' . $col[0]->person_id)}}">{{$col[0]->person->abbreviation}}</a> &amp;
<a href="{{url('persons/' . $col[1]->person_id)}}">{{$col[1]->person->abbreviation}}</a> 
@elseif ($col->count() > 2)
<a href="{{url('persons/' . $col[0]->person_id)}}">{{$col[0]->person->abbreviation}}</a> et al.
@endif
    </span>
<a href="{{url('pictures/' . $picture->id)}}">
    <img src="{{$picture->url(true)}}" class="picture">
</a>
@if ($picture->description != 'Missing translation')
    <span class="picture-description">
<?php
$txt = $picture->description;
if (strlen($txt) > 100) {
    $txt = substr($txt, 0,100) . "...";
} 
echo $txt;
?>
    </span>
@endif
</td>
@endforeach
</tr>
    </table>
    </div>
</div>
<style>
.picture-table {
    border-collapse: separate;
    border-spacing: 2px;
}

.picture-table td {
    border: 1px solid #d3e0e9;
    border-radius: 4px;
    min-width: 170px;
    min-height: 170px;
    vertical-align: top;
}
.picture-credits, .picture-description {
    width: 100%;
    min-height: 15px;
    display: block;
    text-align: center;
    font-size: 12px;
}
</style>
