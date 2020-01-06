import Register from 'framework/common/utils/register';

export default class ProductFilterBox {

    constructor () {
        $('.js-product-filter-open-button').click(event => {
            $(event.target).toggleClass('active');
            $('.js-product-filter').toggleClass('active');
        });

        const _this = this;
        $('.js-product-filter-box-arrow').on('click', event => {
            _this.toggleFilterBox($(event.target).closest('.js-product-filter-box'));
        });
    }

    toggleFilterBox ($parameterContainer) {
        const $productFilterParameterLabel = $parameterContainer.find('.js-product-filter-box-label');
        $productFilterParameterLabel.toggleClass('active');

        const parameterFilterFormId = $parameterContainer.data('product-filter-box-id');

        if ($productFilterParameterLabel.hasClass('active')) {
            $parameterContainer.find('#' + parameterFilterFormId).slideDown('fast');
        } else {
            $parameterContainer.find('#' + parameterFilterFormId).slideUp('fast');
        }
    }

    static init () {
        // eslint-disable-next-line no-new
        new ProductFilterBox();
    }
}

(new Register()).registerCallback(ProductFilterBox.init);
