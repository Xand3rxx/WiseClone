<div class="table-responsive">
    <!--begin::Table-->
    <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_table_users">
        <!--begin::Table head-->
        <thead>
            <!--begin::Table row-->
            <tr class="text-start text-muted fw-bolder fs-7 text-uppercase gs-0">
                <th class="text-center">#</th>
                <th class="min-w-125px">From</th>
                <th class="min-w-125px">To</th>
                <th class="text-center">Value</th>
                <th class="text-center">Source Currency</th>
                <th class="text-center">Target Currency</th>
                <th class="text-center">Transaction Type</th>
                <th class="text-center">Status</th>
                <th class="min-w-125px">Date Created</th>
                <th class="text-end min-w-100px">Actions</th>
            </tr>
            <!--end::Table row-->
        </thead>
        <!--end::Table head-->
        <!--begin::Table body-->
        <tbody class="text-gray-600 fw-bold">
            <!--begin::Table row-->
            @foreach ($transactions as $transaction)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $transaction->senderNameFor(auth()->id()) }}</td>
                <td>{{ $transaction->receiverNameFor(auth()->id()) }}</td>
                <td class="text-center text-{{ $transaction->type()->signClass }}">{{$transaction->type()->sign}}{{ !empty($transaction['amount']) ? $transaction->amount() : '0' }}</td>
                <td class="text-center">{{ !empty($transaction['sourceCurrency']['code']) ? $transaction['sourceCurrency']['code'] : 'Unavailable' }}</td>
                <td class="text-center">{{ !empty($transaction['targetCurrency']['code']) ? $transaction['targetCurrency']['code'] : 'Unavailable' }}</td>
                <td class="text-center">
                    <div class="badge badge-{{ $transaction->type()->class }} fw-bolder">{{ !empty($transaction['type']) ? $transaction->type()->name : 'Unavailable'}}</div>
                </td>
                <td class="text-center">
                    <div class="badge badge-{{ $transaction->status()->class }} fw-bolder">{{ !empty($transaction['status']) ? $transaction->status()->name : 'Unavailable'}}</div>
                </td>
                <td>{{ Carbon\Carbon::parse($transaction['created_at'], 'UTC')->isoFormat('MMMM Do YYYY, h:mm:ssa') }}</td>
                <td class="text-end">
                    <a data-url="{{ route('transaction.show', $transaction['uuid']) }}" class="btn btn-sm btn-primary fw-bolder w-100 w-lg-auto transfer-details-modal" data-bs-toggle="modal" data-bs-target="#transfer_details_modal" title="View transaction details">Details</a>
                    </div>
                </td>
            </tr>
            @endforeach
            <!--end::Table row-->
        </tbody>
        <!--end::Table body-->
    </table>
    <!--end::Table-->
</div>
