$(document).ready(function() {
    $('#clientform').bootstrapValidator({
        fields: {
            name: {
                validators: {
                    notEmpty: {
                        message: '请填写客户'
                    },
                    stringLength: {
                        min: 2,
                        max: 50,
                        message: '名字请在2-50个字符之间~'
                    }
                }
            },
            lot: {
                validators: {
                    notEmpty: {
                        message: '请填写该客户的Lot'
                    }
                }
            },

            balance: {
                validators: {
                    notEmpty: {
                        message: 'balance不能为空'
                    },
                    between:{
                        min:-100000000,
                        max:10000000,
                        message:'请输入合法的数值'
                    }
                }
            },
            sale: {
                validators: {
                    notEmpty: {
                        message: '请填写销售顾问'
                    }
                }
            },
            referencenum: {
                validators: {
                    stringLength: {
                        min: 5,
                        max: 5,
                        message: 'Reference Number应为5位字符'
                    }
                }
            },
            profile: {
                validators: {
                    notEmpty: {
                        message: '请填写该客户的档案地址'
                    },
                    stringLength: {
                        min: 1,
                        max: 100,
                        message: '档案地址最多为100位字符'
                    }
                }
            }
        }
    });

    var html='<div class="row"> <div class="col-xs-6"> <input style="margin-bottom: 14px" type="text" name="lot[]" placeholder="例如:Lot 188 Saltwater" class="form-control"> </div> <div class="col-xs-3"> <input style="margin-bottom: 14px;" type="text" name="loancomp[]" placeholder="贷款公司" class="form-control"> </div> <div class="col-xs-3"> <input style="margin-bottom: 14px;" type="text" name="passtime[]" placeholder="过户时间"  data-date-format="yyyy-mm-dd"  class="form-control reg_time"> </div> </div>'
    //add点击新增输入框

    function datetimepickerconfig() {
        $('.reg_time').datetimepicker({
            weekStart: 1,
            todayBtn:  1,
            autoclose: 1,
            todayHighlight: 1,
            startView: 2,
            minView: 3,
            forceParse: 1,

        });
        $('.reg_time').datetimepicker('refresh');
    };
    datetimepickerconfig();

    $('#add').click(function () {
       alert('hi')
    })



    $('.navbar-nav li:eq(6)').addClass('active');
});