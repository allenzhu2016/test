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
                        message: '请填写该客户的Lot'
                    },
                    between:{
                        min:0,
                        max:1000000,
                        message:'请输入0-1,000,000之间的数字'
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
            }
        }
    });

    $('.navbar-nav li:eq(6)').addClass('active');
});