<?php
/**
 * Customer Membership Module
 *
 * Enable customers to purchase memberships for special pricing on products
 *
 * @author    Your Name
 * @copyright Copyright (c) 2025
 * @license   Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Membership extends Module
{
    /**
     * Module constructor
     */
    public function __construct()
    {
        $this->name = 'membership';
        $this->tab = 'pricing_promotion';
        $this->version = '1.0.0';
        $this->author = 'Your Name';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Customer Membership');
        $this->description = $this->l('Enable customer memberships with special pricing and benefits for 12 months');
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Install module and create database tables
     *
     * @return bool
     */
    public function install()
    {
        return parent::install()
            && $this->createDatabaseTables()
            && $this->registerHook('actionValidateOrder')
            && $this->registerHook('displayAdminProductsExtra')
            && $this->registerHook('actionProductSave')
            && $this->registerHook('displayProductPriceBlock')
            && $this->registerHook('displayShoppingCartFooter')
            && $this->registerHook('actionProductDelete')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionGetProductPropertiesAfter')
            && $this->registerHook('displayProductButtons')
            && Configuration::updateValue('MEMBERSHIP_PRODUCT_ID', 0)
            && Configuration::updateValue('MEMBERSHIP_DURATION_MONTHS', 12);
    }

    /**
     * Uninstall module and clean up database
     *
     * @return bool
     */
    public function uninstall()
    {
        return $this->dropDatabaseTables()
            && Configuration::deleteByName('MEMBERSHIP_PRODUCT_ID')
            && Configuration::deleteByName('MEMBERSHIP_DURATION_MONTHS')
            && parent::uninstall();
    }

    /**
     * Create required database tables
     *
     * @return bool
     */
    protected function createDatabaseTables()
    {
        $sql = [];

        // Table to store customer memberships
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'membership_customer` (
            `id_membership` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `date_start` DATETIME NOT NULL,
            `date_end` DATETIME NOT NULL,
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_membership`),
            KEY `id_customer` (`id_customer`),
            KEY `id_order` (`id_order`),
            KEY `active` (`active`),
            KEY `date_end` (`date_end`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        // Table to store member prices for products
        $sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'membership_product_price` (
            `id_membership_price` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_product` INT(11) UNSIGNED NOT NULL,
            `member_price` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `reduction_type` ENUM("amount", "percentage") NOT NULL DEFAULT "percentage",
            `reduction_value` DECIMAL(20, 6) NOT NULL DEFAULT 0.000000,
            `date_add` DATETIME NOT NULL,
            `date_upd` DATETIME NOT NULL,
            PRIMARY KEY (`id_membership_price`),
            UNIQUE KEY `id_product` (`id_product`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Drop database tables on uninstall
     *
     * @return bool
     */
    protected function dropDatabaseTables()
    {
        $sql = [
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'membership_customer`',
            'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'membership_product_price`'
        ];

        foreach ($sql as $query) {
            if (!Db::getInstance()->execute($query)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Module configuration page
     *
     * @return string HTML content
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitMembershipConfig')) {
            $output .= $this->postProcess();
        }

        return $output . $this->renderConfigForm();
    }

    /**
     * Process configuration form submission
     *
     * @return string Confirmation or error message
     */
    protected function postProcess()
    {
        $membershipProductId = (int) Tools::getValue('MEMBERSHIP_PRODUCT_ID');
        $membershipDuration = (int) Tools::getValue('MEMBERSHIP_DURATION_MONTHS');

        if ($membershipProductId <= 0) {
            return $this->displayError($this->l('Membership Product: Please select a valid product from the dropdown.'));
        }

        if ($membershipDuration <= 0) {
            return $this->displayError(
                sprintf($this->l('Membership Duration: Value must be greater than 0 months. Entered: %d'), $membershipDuration)
            );
        }

        if ($membershipDuration > 120) {
            return $this->displayError(
                sprintf($this->l('Membership Duration: Maximum duration is 120 months (10 years). Entered: %d'), $membershipDuration)
            );
        }

        Configuration::updateValue('MEMBERSHIP_PRODUCT_ID', $membershipProductId);
        Configuration::updateValue('MEMBERSHIP_DURATION_MONTHS', $membershipDuration);

        return $this->displayConfirmation(
            sprintf($this->l('Settings updated successfully. Membership Product ID: %d, Duration: %d months'), $membershipProductId, $membershipDuration)
        );
    }

    /**
     * Render configuration form
     *
     * @return string HTML form
     */
    protected function renderConfigForm()
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submitMembershipConfig';
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name .
                    '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            ]
        ];

        // Get list of products for dropdown
        $productOptions = [[
            'id' => 0,
            'name' => $this->l('-- Select a product --')
        ]];

        try {
            $products = Product::getProducts($this->context->language->id, 0, 0, 'id_product', 'ASC');

            if (is_array($products) && !empty($products)) {
                foreach ($products as $product) {
                    if (isset($product['id_product']) && isset($product['name'])) {
                        $productOptions[] = [
                            'id' => (int) $product['id_product'],
                            'name' => $product['name'] . ' (ID: ' . $product['id_product'] . ')'
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Membership module: Error fetching products - ' . $e->getMessage(), 3);
        }

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Membership Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->l('Membership Product'),
                        'name' => 'MEMBERSHIP_PRODUCT_ID',
                        'desc' => $this->l('Select the product that customers purchase to become members. This should be a digital/virtual product.'),
                        'options' => [
                            'query' => $productOptions,
                            'id' => 'id',
                            'name' => 'name'
                        ],
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Membership Duration (months)'),
                        'name' => 'MEMBERSHIP_DURATION_MONTHS',
                        'desc' => $this->l('How many months a membership lasts after purchase (default: 12)'),
                        'class' => 'fixed-width-sm',
                        'required' => true
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper->fields_value['MEMBERSHIP_PRODUCT_ID'] = Configuration::get('MEMBERSHIP_PRODUCT_ID');
        $helper->fields_value['MEMBERSHIP_DURATION_MONTHS'] = Configuration::get('MEMBERSHIP_DURATION_MONTHS');

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Hook: Display admin form to add member pricing to products
     *
     * @param array $params Hook parameters
     * @return string HTML form
     */
    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int) $params['id_product'];
        $memberPrice = $this->getMemberPrice($id_product);

        $this->context->smarty->assign([
            'member_price_enabled' => $memberPrice ? true : false,
            'member_price_reduction_type' => $memberPrice ? $memberPrice['reduction_type'] : 'percentage',
            'member_price_reduction_value' => $memberPrice ? $memberPrice['reduction_value'] : 0,
            'member_price_fixed' => $memberPrice ? $memberPrice['member_price'] : 0,
            'currency' => $this->context->currency,
        ]);

        return $this->display(__FILE__, 'views/templates/admin/product_member_price.tpl');
    }

    /**
     * Hook: Save member pricing when product is saved
     *
     * @param array $params Hook parameters
     * @return bool
     */
    public function hookActionProductSave($params)
    {
        $id_product = (int) $params['id_product'];

        if (!$id_product) {
            return false;
        }

        // Check if member pricing is enabled for this product
        $enableMemberPrice = Tools::getValue('enable_member_price');

        if ($enableMemberPrice) {
            $reductionType = Tools::getValue('member_price_reduction_type', 'percentage');
            $reductionValue = (float) Tools::getValue('member_price_reduction_value', 0);
            $memberPriceFixed = (float) Tools::getValue('member_price_fixed', 0);

            // Validate reduction type
            if (!in_array($reductionType, ['percentage', 'amount'])) {
                PrestaShopLogger::addLog('Membership module: Invalid reduction type: ' . $reductionType, 2);
                return false;
            }

            // Validate values are not negative
            if ($reductionValue < 0 || $memberPriceFixed < 0) {
                PrestaShopLogger::addLog('Membership module: Negative values not allowed for product ' . $id_product, 2);
                return false;
            }

            return $this->saveMemberPrice($id_product, $memberPriceFixed, $reductionType, $reductionValue);
        } else {
            // Remove member pricing if disabled
            return $this->deleteMemberPrice($id_product);
        }
    }

    /**
     * Hook: Delete member pricing when product is deleted
     *
     * @param array $params Hook parameters
     * @return bool
     */
    public function hookActionProductDelete($params)
    {
        $id_product = (int) $params['id_product'];
        return $this->deleteMemberPrice($id_product);
    }

    /**
     * Hook: Grant membership when order is validated
     *
     * @param array $params Hook parameters
     * @return bool
     */
    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cart = $params['cart'];
        $customer = new Customer($cart->id_customer);

        if (!Validate::isLoadedObject($customer)) {
            return false;
        }

        $membershipProductId = (int) Configuration::get('MEMBERSHIP_PRODUCT_ID');
        if (!$membershipProductId) {
            return false;
        }

        // Check if order contains membership product
        $products = $order->getProducts();
        $hasMembershipProduct = false;

        foreach ($products as $product) {
            if ((int) $product['product_id'] == $membershipProductId) {
                $hasMembershipProduct = true;
                break;
            }
        }

        if ($hasMembershipProduct) {
            return $this->grantMembership($customer->id, $order->id);
        }

        return true;
    }

    /**
     * Hook: Display member price on product pages
     *
     * @param array $params Hook parameters
     * @return string HTML content
     */
    public function hookDisplayProductPriceBlock($params)
    {
        if ($params['type'] !== 'after_price') {
            return '';
        }

        $id_product = (int) $params['product']['id_product'];
        $memberPrice = $this->getMemberPrice($id_product);

        if (!$memberPrice) {
            return '';
        }

        $customer = $this->context->customer;
        $isMember = $customer->isLogged() && $this->isActiveMember($customer->id);

        if ($isMember) {
            return ''; // Members already see member price
        }

        // Show potential savings to non-members
        $product = $params['product'];

        // Safely get original price with fallback
        $originalPrice = 0;
        if (isset($product['price_amount'])) {
            $originalPrice = (float) $product['price_amount'];
        } elseif (isset($product['price_tax_incl'])) {
            $originalPrice = (float) $product['price_tax_incl'];
        } elseif (isset($product['price_wt'])) {
            $originalPrice = (float) $product['price_wt'];
        } elseif (isset($product['price'])) {
            $originalPrice = (float) $product['price'];
        }

        if ($originalPrice <= 0) {
            return ''; // No valid price found
        }

        $calculatedMemberPrice = $this->calculateMemberPrice($originalPrice, $memberPrice);
        $savings = $originalPrice - $calculatedMemberPrice;

        if ($savings <= 0) {
            return '';
        }

        $this->context->smarty->assign([
            'member_price' => $calculatedMemberPrice,
            'original_price' => $originalPrice,
            'savings' => $savings,
            'is_logged' => $customer->isLogged(),
            'currency' => $this->context->currency,
            'link' => $this->context->link,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/product_member_price_info.tpl');
    }

    /**
     * Hook: Display savings calculator in shopping cart
     *
     * @param array $params Hook parameters
     * @return string HTML content
     */
    public function hookDisplayShoppingCartFooter($params)
    {
        $customer = $this->context->customer;

        // Only show to non-members
        if ($customer->isLogged() && $this->isActiveMember($customer->id)) {
            return '';
        }

        $cart = $this->context->cart;
        if (!Validate::isLoadedObject($cart)) {
            return '';
        }

        $products = $cart->getProducts();
        $totalSavings = 0;
        $potentialMemberTotal = 0;
        $currentTotal = 0;

        foreach ($products as $product) {
            $id_product = (int) $product['id_product'];
            $memberPrice = $this->getMemberPrice($id_product);

            $productPrice = (float) $product['price_wt'];
            $quantity = (int) $product['cart_quantity'];

            $currentTotal += $productPrice * $quantity;

            if ($memberPrice) {
                $calculatedMemberPrice = $this->calculateMemberPrice($productPrice, $memberPrice);
                $potentialMemberTotal += $calculatedMemberPrice * $quantity;
                $totalSavings += ($productPrice - $calculatedMemberPrice) * $quantity;
            } else {
                $potentialMemberTotal += $productPrice * $quantity;
            }
        }

        if ($totalSavings <= 0) {
            return '';
        }

        $membershipProductId = (int) Configuration::get('MEMBERSHIP_PRODUCT_ID');
        $membershipPrice = 0;

        if ($membershipProductId) {
            $membershipProduct = new Product(
                $membershipProductId,
                false,
                $this->context->language->id,
                (int) $this->context->shop->id
            );
            if (Validate::isLoadedObject($membershipProduct)) {
                $membershipPrice = $membershipProduct->getPrice(true);
            }
        }

        // Calculate break-even point
        $ordersToBreakEven = $membershipPrice > 0 && $totalSavings > 0
            ? ceil($membershipPrice / $totalSavings)
            : 0;
        $alreadySaved = $totalSavings >= $membershipPrice;

        $this->context->smarty->assign([
            'total_savings' => $totalSavings,
            'membership_price' => $membershipPrice,
            'orders_to_break_even' => $ordersToBreakEven,
            'already_saved' => $alreadySaved,
            'is_logged' => $customer->isLogged(),
            'membership_product_id' => $membershipProductId,
            'currency' => $this->context->currency,
            'link' => $this->context->link,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/cart_savings_calculator.tpl');
    }

    /**
     * Hook: Add CSS to header
     *
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addCSS($this->_path . 'views/css/membership.css');
    }

    /**
     * Hook: Modify product properties to apply member pricing
     *
     * @param array $params Hook parameters
     * @return void
     */
    public function hookActionGetProductPropertiesAfter($params)
    {
        $customer = $this->context->customer;

        // Only apply member pricing to logged-in members
        if (!$customer->isLogged() || !$this->isActiveMember($customer->id)) {
            return;
        }

        $product = &$params['product'];
        $id_product = (int) $product['id_product'];
        $memberPrice = $this->getMemberPrice($id_product);

        if (!$memberPrice) {
            return;
        }

        // Calculate and apply member price
        $originalPrice = (float) $product['price'];
        $calculatedMemberPrice = $this->calculateMemberPrice($originalPrice, $memberPrice);

        if ($calculatedMemberPrice < $originalPrice) {
            $product['price'] = $calculatedMemberPrice;
            $product['price_amount'] = $calculatedMemberPrice;

            // Also update price_tax_exc if available
            if (isset($product['price_tax_exc'])) {
                $taxRate = isset($product['rate']) ? (float) $product['rate'] : 0;
                if ($taxRate > 0) {
                    $product['price_tax_exc'] = $calculatedMemberPrice / (1 + ($taxRate / 100));
                } else {
                    $product['price_tax_exc'] = $calculatedMemberPrice;
                }
            }

            // Mark that member pricing was applied
            $product['is_member_price'] = true;
        }
    }

    /**
     * Hook: Display member badge on product page
     *
     * @param array $params Hook parameters
     * @return string HTML content
     */
    public function hookDisplayProductButtons($params)
    {
        $customer = $this->context->customer;

        // Only show badge to members
        if (!$customer->isLogged() || !$this->isActiveMember($customer->id)) {
            return '';
        }

        $id_product = 0;
        if (isset($params['product'])) {
            $product = $params['product'];

            if (is_array($product) || $product instanceof ArrayAccess) {
                if (isset($product['id_product'])) {
                    $id_product = (int) $product['id_product'];
                } elseif (isset($product['id'])) {
                    $id_product = (int) $product['id'];
                }
            } elseif (is_object($product)) {
                if (isset($product->id)) {
                    $id_product = (int) $product->id;
                } elseif (isset($product->id_product)) {
                    $id_product = (int) $product->id_product;
                }
            }
        }
        if (!$id_product) {
            return '';
        }

        $memberPrice = $this->getMemberPrice($id_product);
        if (!$memberPrice) {
            return '';
        }

        $this->context->smarty->assign([
            'is_member' => true,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/member_badge.tpl');
    }

    /**
     * Get member price for a product
     *
     * @param int $id_product Product ID
     * @return array|false Member price data or false
     */
    protected function getMemberPrice($id_product)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'membership_product_price`
                WHERE `id_product` = ' . (int) $id_product;

        return Db::getInstance()->getRow($sql);
    }

    /**
     * Save member price for a product
     *
     * @param int $id_product Product ID
     * @param float $memberPrice Fixed member price
     * @param string $reductionType Type of reduction (amount or percentage)
     * @param float $reductionValue Reduction value
     * @return bool Success status
     */
    protected function saveMemberPrice($id_product, $memberPrice, $reductionType, $reductionValue)
    {
        $existingPrice = $this->getMemberPrice($id_product);
        $now = date('Y-m-d H:i:s');

        $data = [
            'id_product' => (int) $id_product,
            'member_price' => (float) $memberPrice,
            'reduction_type' => pSQL($reductionType),
            'reduction_value' => (float) $reductionValue,
            'date_upd' => pSQL($now),
        ];

        if ($existingPrice) {
            return Db::getInstance()->update(
                'membership_product_price',
                $data,
                '`id_product` = ' . (int) $id_product
            );
        } else {
            $data['date_add'] = pSQL($now);
            return Db::getInstance()->insert('membership_product_price', $data);
        }
    }

    /**
     * Delete member price for a product
     *
     * @param int $id_product Product ID
     * @return bool Success status
     */
    protected function deleteMemberPrice($id_product)
    {
        return Db::getInstance()->delete(
            'membership_product_price',
            '`id_product` = ' . (int) $id_product
        );
    }

    /**
     * Calculate member price based on original price and reduction
     *
     * @param float $originalPrice Original product price
     * @param array $memberPriceData Member price configuration
     * @return float Calculated member price
     */
    protected function calculateMemberPrice($originalPrice, $memberPriceData)
    {
        // Validate input
        if (!is_array($memberPriceData) || empty($memberPriceData)) {
            return $originalPrice;
        }

        // Check if fixed member price is set
        if (isset($memberPriceData['member_price']) && $memberPriceData['member_price'] > 0) {
            return (float) $memberPriceData['member_price'];
        }

        // Check reduction type and value exist
        if (!isset($memberPriceData['reduction_type']) || !isset($memberPriceData['reduction_value'])) {
            return $originalPrice;
        }

        // Apply percentage reduction
        if ($memberPriceData['reduction_type'] === 'percentage') {
            $reductionValue = (float) $memberPriceData['reduction_value'];
            if ($reductionValue > 0) {
                $reduction = $originalPrice * ($reductionValue / 100);
                return max(0, $originalPrice - $reduction);
            }
        }
        // Apply amount reduction
        elseif ($memberPriceData['reduction_type'] === 'amount') {
            $reductionValue = (float) $memberPriceData['reduction_value'];
            if ($reductionValue > 0) {
                return max(0, $originalPrice - $reductionValue);
            }
        }

        return $originalPrice;
    }

    /**
     * Grant membership to a customer
     *
     * @param int $id_customer Customer ID
     * @param int $id_order Order ID
     * @return bool Success status
     */
    protected function grantMembership($id_customer, $id_order)
    {
        $durationMonths = (int) Configuration::get('MEMBERSHIP_DURATION_MONTHS');
        $nowDate = new DateTime();
        $startDate = clone $nowDate;

        $existingMembership = $this->getActiveMembership($id_customer);
        if ($existingMembership && !empty($existingMembership['date_end'])) {
            try {
                $existingEnd = new DateTime($existingMembership['date_end']);
                if ($existingEnd > $startDate) {
                    $startDate = $existingEnd;
                }
            } catch (Exception $e) {
                // Ignore invalid stored dates and start from now
            }
        }

        $interval = DateInterval::createFromDateString((int) $durationMonths . ' months');
        if ($interval === false) {
            $interval = DateInterval::createFromDateString('0 months');
        }

        $endDate = clone $startDate;
        $endDate->add($interval);

        $recordStart = $startDate > $nowDate ? clone $startDate : clone $nowDate;

        $now = $nowDate->format('Y-m-d H:i:s');
        $dateStart = $recordStart->format('Y-m-d H:i:s');
        $dateEnd = $endDate->format('Y-m-d H:i:s');

        $db = Db::getInstance();

        // Use transaction to ensure data consistency
        $db->execute('START TRANSACTION');

        try {
            // Deactivate any existing memberships for this customer
            $updateResult = $db->update(
                'membership_customer',
                ['active' => 0, 'date_upd' => pSQL($now)],
                '`id_customer` = ' . (int) $id_customer
            );

            // Create new membership
            $insertResult = $db->insert('membership_customer', [
                'id_customer' => (int) $id_customer,
                'id_order' => (int) $id_order,
                'date_start' => pSQL($dateStart),
                'date_end' => pSQL($dateEnd),
                'active' => 1,
                'date_add' => pSQL($now),
                'date_upd' => pSQL($now),
            ]);

            if ($insertResult) {
                $db->execute('COMMIT');
                return true;
            } else {
                $db->execute('ROLLBACK');
                PrestaShopLogger::addLog('Membership module: Failed to grant membership for customer ' . (int) $id_customer, 3);
                return false;
            }
        } catch (Exception $e) {
            $db->execute('ROLLBACK');
            PrestaShopLogger::addLog('Membership module: Exception granting membership - ' . $e->getMessage(), 3);
            return false;
        }
    }

    /**
     * Check if customer has an active membership
     *
     * @param int $id_customer Customer ID
     * @return bool True if customer is an active member
     */
    public function isActiveMember($id_customer)
    {
        if (!$id_customer) {
            return false;
        }

        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'membership_customer`
                WHERE `id_customer` = ' . (int) $id_customer . '
                AND `active` = 1
                AND `date_end` >= NOW()';

        return (bool) Db::getInstance()->getValue($sql);
    }

    /**
     * Get active membership for a customer
     *
     * @param int $id_customer Customer ID
     * @return array|false Membership data or false
     */
    public function getActiveMembership($id_customer)
    {
        if (!$id_customer) {
            return false;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'membership_customer`
                WHERE `id_customer` = ' . (int) $id_customer . '
                AND `active` = 1
                AND `date_end` >= NOW()
                ORDER BY `date_end` DESC
                LIMIT 1';

        return Db::getInstance()->getRow($sql);
    }
}
