# Release Notes for PAYONE

## 2.7.0 (2023-03-26)

### Changed
- Rework of the assistant: password validation.

## 2.6.0 (2023-05-24)

### Fixed
- Notes on invoice documents are now printed in the appropriate language.
- 3D secure workflow

## 2.5.3 (2022-10-27)

### Fixed
- Payments can now be completed again in all cases.

## 2.5.2

### Fixed
- If there was an error during a refund, it was not displayed correctly.
- Fixed the error that occurred during the refund.

## 2.5.1

### Fixed
- The SEO settings regarding the trailing slash are now correctly taken into account when building the routes, so that no incorrect redirects occur.

## 2.5.0

### Added
- The payment methods of the plugin can now also be used in MyAccount to pay for an order afterwards.

## 2.4.0

### Added
* The following payment methods have been added:
 
  - Klarna "Slice It" (Installments)
  - Klarna "Pay Later" (Invoice)
  - Klarna "Pay Now" (Direct Debit)
  - Klarna "Pay Now" (Direct Bank Transfer)

* In the assistant, an individual icon for each payment method can now be stored for the webshop.

## 2.3.1

### Fixed
- An error could occur in the case of secure invoice purchase in connection with order item properties or item properties. This has been fixed.

## 2.3.0

### Added
- The selection of available delivery countries has been extended.

### Fixed
- The item data is now transferred correctly when using the secure invoice method.

## 2.2.0

### Added
- When copying a plugin set, the settings of the assistant are now also copied.

## 2.1.3

### Fixed
- When checking whether a payment method is available for subsequent payment, the same rules that are applied to the checkout als apply here.
- Trailing slash setting from the backend is now processed correctly.

## 2.1.2

### Fixed
- The allowed types of credit cards were not displayed correctly. This has now been fixed.

## 2.1.1

### Fixed
- Due to a faulty configuration of the plugin there could be errors in the webshop, this has been fixed.

## 2.1.0

### Added
- Order item order properties can now be processed correctly.

## 2.0.0

### TODO
- To configure the plugin, it is necessary to complete the assistant in each linked plugin set.

### Hinzugefügt
- The settings for the Payone plugin have been transferred to an assistant. It is now possible to configure the plugin per plugin set and per client.

## 1.3.0

### Added
- The allowed delivery countries can now be selected in the configuration of the plugin via checkbox.

### Changed
- No invoices can be created for orders paid with secure invoice.

## 1.2.2

### Fixed
- An error in the checkout process with the payment method "credit card" was fixed.

## 1.2.1

### Fixed
- Save payment amount in foreign currency if the system currency is not the same as the basket currency.

## 1.2.0

### Added
- Payment method "Amazon Pay" added

## 1.1.14

### Added
- Payment method "Secure Invoice" added

## 1.1.13

### Changed
- Added Icon for the backend
- Updated user guide

## 1.1.12

### Changed
- Optimized logging of the data transfer with the Payone Api.

## 1.1.11

### Changed
- New logos and images
- Added methods for the backend visibility and backend name

## 1.1.10

### Fixed
- Writing Order notes during a refund works properly again.

## 1.1.9

### Fixed
- Partial refunds can now be executed more than once. Under certain constellations an error came up.

## 1.1.8

### Fixed
- Partial refunds will use the correct amount from the credit note or return.

### Changed
- Refund payments will added to the credit note or return from where they were executed.
- The loading times of the plugin have been improved.

## 1.1.7

### Added
- An order note will added for refunds. Please add an user ID in the plugin configurations.

## 1.1.6

### Fixed
- Fix wrong order creation

## 1.1.5

### Fixed
- External orders will be created correct without skipping by the plugin.

## 1.1.4

### Changed
- The Payone SDK was relocated

### Added
- The payment method Amazon Pay was added for the backend.

### Fixed
- Some log messages

## 1.1.3

### Changed
- Optimisation for Entering the date of birth in the checkout process.
- Updated user guide

## 1.1.2

### Changed
- Entering the date of birth is now mandatory in the checkout process. The field for the date of birth must be activated in the Ceres settings for the checkout process.

### Added
- Net orders can now be executed.

## 1.1.1

### Fixed
- An error causing the creation of duplicate orders during payment processing has been fixed
- An error with the credit card type selection was fixed

## 1.1.0

### Changed
- Support is now within the responsibility of plentysystems
- Added new icons and customised descriptions
- Updated German and English user guides

## 1.0.9

### Changed
- Added alert text to user guide

## 1.0.8

### Changed
- Updated information in the support tab
- Updated changelog

## 1.0.7

### Updated
- Updated the config.json file to implement new plugin format

### Added
- Added translations

### Changed
- Updated user guide
- Implemented guzzle/httpguzzle version in dependency for PayPal compatibility

## 1.0.6

### Added
- Sofort is now displayed in the front end
- Paydirekt is now displayed in the front end
- Secure invoice is now displayed in the front end
- PayPal is now displayed in the front end

### Changed
- Improved rendering of payment error message

## 1.0.5

### Changed
- Updated logos and plugin name

## 1.0.4

### Changed
- Updated documentation

## 1.0.3

### Added
- Added English documentation

### Changed
- The current Payone PHP API is now used

## 1.0.2

### Changed
- Scriptloader is now used to include Payone scripts in templates

## 1.0.1

### Changed
- Update plugin documentation

## 1.0.1
Plugin release supporting the following payment methods:

- Invoice
- Prepayment
- Cash on delivery
- Debit payment
- Credit card
- Credit card 3DS
