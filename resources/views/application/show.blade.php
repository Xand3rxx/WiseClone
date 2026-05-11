<div class="pb-5 fs-6">
    <!--begin::Details item-->
    <div class="fw-bolder mt-5">Transfer From</div>
    <div class="text-gray-600">{{ $transaction->senderNameFor(auth()->id()) }}</div>

    <div class="fw-bolder mt-5">Transfer To</div>
    <div class="text-gray-600">{{ $transaction->receiverNameFor(auth()->id()) }}</div>

    <div class="fw-bolder mt-5">Source Currency</div>
    <div class="text-gray-600">{{ !empty($transaction['sourceCurrency']['code']) ? $transaction['sourceCurrency']['code'] : ''}} - {{ !empty($transaction['sourceCurrency']['name']) ? $transaction['sourceCurrency']['name'] : 'Unavailable'}}</div>

    <div class="fw-bolder mt-5">Target Currency</div>
    <div class="text-gray-600">{{ !empty($transaction['targetCurrency']['code']) ? $transaction['targetCurrency']['code'] : ''}} - {{ !empty($transaction['targetCurrency']['name']) ? $transaction['targetCurrency']['name'] : 'Unavailable'}}</div>

    <div class="fw-bolder mt-5">Transaction Reference</div>
    <div class="text-gray-600">{{ !empty($transaction['reference']) ? $transaction['reference'] : 'Unavailable' }}</div>

    <div class="fw-bolder mt-5">Amount</div>
    <div class="text-gray-600">{{ !empty($transaction['amount']) ? $transaction->amount() : '0' }}</div>

    <div class="fw-bolder mt-5">Exchange Rate</div>
    <div class="text-gray-600">{{ !empty($transaction['rate']) ? $transaction['rate'] : '0' }}</div>

    <div class="fw-bolder mt-5">Transfer Fee</div>
    <div class="text-gray-600">{{ !empty($transaction['transfer_fee']) ? $transaction['transfer_fee'] : '0' }} {{ !empty($transaction['targetCurrency']['code']) ? $transaction['targetCurrency']['code'] : ''}}</div>

    <div class="fw-bolder mt-5">Variable Fee</div>
    <div class="text-gray-600">{{ !empty($transaction['variable_fee']) ? $transaction['variable_fee'] : '0' }} {{ !empty($transaction['sourceCurrency']['code']) ? $transaction['sourceCurrency']['code'] : ''}}</div>

    <div class="fw-bolder mt-5">Fixed Fee</div>
    <div class="text-gray-600">{{ !empty($transaction['fixed_fee']) ? $transaction['fixed_fee'] : '0' }}</div>

    <div class="fw-bolder mt-5">Transaction Type</div>
    <div class="badge badge-{{ $transaction->type()->class }}">{{ !empty($transaction['type']) ? $transaction->type()->name : 'Unavailable'}}</div>

    <div class="fw-bolder mt-5">Transaction Status</div>
    <div class="badge badge-{{ $transaction->status()->class }}">{{ !empty($transaction['type']) ? $transaction->status()->name : 'Unavailable'}}</div>



    <div class="fw-bolder mt-5">Transaction Date</div>
    <div class="text-gray-600">{{ Carbon\Carbon::parse($transaction['created_at'], 'UTC')->isoFormat('MMMM Do YYYY, h:mm:ssa') }}</div>


    <!--begin::Details item-->
</div>
