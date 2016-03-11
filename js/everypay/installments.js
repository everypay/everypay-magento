var installments = [];
var tableTemplate = '<table class="table"><thead><tr><th width="154">Amount From</th><th width="154">Amount To</th> <th width="154">Installments</th> <th><button type="button" class="add" id="add-installment"><span></span></button></th> </tr> </thead> <tbody></tbody> </table>';
var rowTemplate = '<tr data-id="{{id}}"> <td><input type="text" name="amount_{{id}}_from" value="{{from}}" class="form-control" /></td> <td><input type="text" name="amount_{{id}}_to" value="{{to}}" class="form-control" /></td> <td><input type="text" name="max_{{id}}" value="{{max}}" class="form-control" /></td> <td><button class="delete remove-installment"><span></span></button></td> </tr>';
$j(function() {
    var table = tableTemplate;
    Mustache.parse(table);
    var renderedTable = Mustache.render(table, {});
    $j('#payment_everypay').append($j(renderedTable));

    var input = $j('#payment_everypay_installments').val();
    if (input) {
        installments = JSON.parse(input);
        createElements();
    }

    $j('#add-installment').click(function (e) {
        e.preventDefault();
        var maxRows = maxElementIndex();

        var row = rowTemplate;
        Mustache.parse(row);
        var element = {id: maxRows, from: 0, to: 100, max: 12};
        var renderedRow = Mustache.render(row, element);
        $row = $j(renderedRow);
        addInstallment($row);
        $row.find('input').change(function (e){
            addInstallment($j(this).parent().parent());
        });
        $j('#payment_everypay .table tbody').append($row);
        $row.find('.remove-installment').click(function (e){
            e.preventDefault();
            removeInstallment($j(this).parent().parent());
            $j(this).parent().parent().remove();
        });
    });
});

var addInstallment = function (row) {
    var element = {
        id: row.attr('data-id'),
        from: row.find('input[name$="from"]').val(),
        to:  row.find('input[name$="to"]').val(),
        max:  row.find('input[name^="max"]').val(),
    }

    index = elementExists(element.id);
    if (false !== index) {
        installments[index] = element;
    } else {
        installments.push(element);
    }
    $j('#payment_everypay_installments').val(JSON.stringify(installments));
};

var removeInstallment = function (row) {
    var index = false;
    var id = row.attr('data-id');
    for (var i = 0, l = installments.length; i < l; i++) {
        if (installments[i].id == id) {
            index = i;
        }
    }

    if (false !== index) {
        installments.splice(index, 1);
    }
    $j('#payment_everypay_installments').val(JSON.stringify(installments));
};

var elementExists = function (id) {
    for (var i = 0, l = installments.length; i < l; i++) {
        if (installments[i].id == id) {
            return i;
        }
    }

    return false;
}

var maxElementIndex = function (row) {
    var length = $j('#payment_everypay .table tbody tr').length;
    if (0 == length) {
        return 1;
    }

    length = $j('#payment_everypay .table tbody tr:last').attr('data-id');
    length = parseInt(length);

    return length + 1;
}

var createElements = function () {
    var row = rowTemplate;
    Mustache.parse(row);
    for (var i = 0, l = installments.length; i < l; i++) {
        var element = installments[i];
        var renderedRow = Mustache.render(row, element);
        $row = $j(renderedRow);
        $row.find('input').change(function (e){
            addInstallment($j(this).parent().parent());
        });
        $j('#payment_everypay .table tbody').append($row);
        $row.find('.remove-installment').click(function (e){
            e.preventDefault();
            removeInstallment($j(this).parent().parent());
            $j(this).parent().parent().remove();
        });
    }
}
