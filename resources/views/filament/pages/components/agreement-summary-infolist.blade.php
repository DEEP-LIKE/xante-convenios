@php
    $data = $this->data;
@endphp

<div class="space-y-6">
    {!! $this->renderHolderSummary($data) !!}
    {!! $this->renderSpouseSummary($data) !!}
    {!! $this->renderPropertySummary($data) !!}
    {!! $this->renderFinancialSummary($data) !!}
</div>


