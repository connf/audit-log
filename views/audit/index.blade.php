@extends('layouts.app')
@section('content')
<div class="col-lg-12">
    <div class="widget">
        <div class="widget-header">
            <h2>Audit Log</h2>
        </div>
        <div class="widget-content">
            <div class="table-responsive">
                <form class='form-horizontal' role='form'>
                    <table id="audit-table" class="table table-striped table-bordered" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                              <th>Table</th>
                              <th>Action</th>
                              <th>Field</th>
                              <th>Old Value</th>
                              <th>New Value</th>
                              <th>User</th>
                              <th>Date</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                              <th>Model</th>
                              <th>Action</th>
                              <th>Field</th>
                              <th>Old Value</th>
                              <th>New Value</th>
                              <th>User</th>
                              <th>Date</th>
                            </tr>
                        </tfoot>
                        <tbody>
{{--
@foreach($audits as $audit)
  @foreach($audit->changes['attributes'] as $field => $value)
  <tr>
    <td>{{ $model }}</td>
    <td>{{ $action }}</td>
    <td>{{ $field }}</td>
    <td>{{ $audit->changes['old'][$field] }}</td>
    <td>{{ $audit->changes['attributes'][$key] }}</td>
  </tr>
  @endforeach
@endforeach
  <tr>
    <td>Table Item [ID: 1234]</td>
    <td>Created</td>
    <td>Representative Name</td>
    <td>-</td>
    <td>Example Name</td>
    <td>Staff Member - Example Staff</td>
    <td>01/02/2019 10:00</td>
  </tr>
--}}
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')

<script>
  (function(){
    $('#audit-table').DataTable({
      "paging":   true,
      "ordering": true,
      "info":     true
    });//Init Data Table
  })();
</script>
@endpush
