@php
    $data = $this->data;
@endphp

<div class="space-y-6">
    {!! $this->renderHolderSummary($data) !!}
    @if(!empty($data['has_co_borrower']) && $data['has_co_borrower'])
        {!! $this->renderSpouseSummary($data) !!}
    @endif
    {!! $this->renderPropertySummary($data) !!}
    {!! $this->renderFinancialSummary($data) !!}
</div>


