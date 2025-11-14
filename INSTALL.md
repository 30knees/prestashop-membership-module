# PrestaShop Membership Module - Installation Guide

## Module Information
- **Module Name:** membership
- **Version:** 1.0.0
- **PrestaShop Compatibility:** 8.0.0 - 8.2.x
- **PHP Requirements:** 7.2.5 or higher

## How to Package the Module

PrestaShop requires modules to be packaged in a specific way. The ZIP file must contain the module folder, not just the files directly.

### Creating the ZIP Package

#### Method 1: Using the Command Line

From the **parent directory** of the module (NOT from inside the membership folder):

```bash
cd /path/to/parent-directory
zip -r membership.zip membership/ -x "*.git*" "*.DS_Store"
```

#### Method 2: Using the Module Directory

From **inside** the membership module directory:

```bash
cd /home/user/prestashop-membership-module
cd ..
zip -r membership.zip membership/ -x "membership/.git/*" "membership/.DS_Store"
```

This will create a `membership.zip` file that contains the `membership/` folder with all module files inside.

### Important Notes

- **The ZIP must contain the folder:** When you extract `membership.zip`, you should see a `membership/` folder, not the files directly
- **Folder name must match module name:** The folder must be named `membership` (lowercase)
- **Exclude version control:** Do not include `.git` directories or other development files

## Installation Steps

1. **Download or Create the ZIP File**
   - Create the ZIP package using the methods above
   - Or download the pre-packaged module ZIP

2. **Access PrestaShop Back Office**
   - Log in to your PrestaShop admin panel
   - Navigate to: **Modules > Module Manager**

3. **Upload the Module**
   - Click on **"Upload a module"** button (top right)
   - Select the `membership.zip` file
   - Click **"Upload this module"**

4. **Install the Module**
   - Once uploaded, find "Customer Membership" in the module list
   - Click the **"Install"** button
   - Follow any on-screen prompts

5. **Configure the Module**
   - After installation, click **"Configure"**
   - Select the product that represents the membership (should be a digital/virtual product)
   - Set the membership duration in months (default: 12)
   - Click **"Save"**

## Module Structure

```
membership/
├── composer.json           # Composer configuration
├── config.xml             # Cached module configuration
├── INSTALL.md             # This installation guide
├── logo.png               # Module icon (32x32 PNG)
├── membership.php         # Main module file
├── README.md              # Module documentation
├── index.php              # Security file
│
├── sql/                   # Database installation scripts
│   └── index.php
│
├── translations/          # Language files
│   ├── cs.php
│   ├── de.php
│   ├── en.php
│   ├── es.php
│   ├── fr.php
│   ├── hr.php
│   ├── it.php
│   ├── nl.php
│   ├── pl.php
│   ├── ro.php
│   ├── sv.php
│   ├── README.md
│   └── index.php
│
├── upgrade/               # Future upgrade scripts
│   └── index.php
│
└── views/                 # Frontend assets and templates
    ├── css/
    │   ├── membership.css
    │   └── index.php
    ├── js/
    │   └── index.php
    ├── templates/
    │   ├── admin/
    │   │   ├── product_member_price.tpl
    │   │   └── index.php
    │   ├── front/
    │   │   └── index.php
    │   ├── hook/
    │   │   ├── cart_savings_calculator.tpl
    │   │   ├── member_badge.tpl
    │   │   ├── product_member_price_info.tpl
    │   │   └── index.php
    │   └── index.php
    └── index.php
```

## Requirements

- **PrestaShop:** 8.0.0 or higher (tested with 8.2.x)
- **PHP:** 7.2.5 or higher
- **MySQL:** 5.6 or higher
- **Server:** Apache or Nginx with mod_rewrite enabled

## Troubleshooting

### ZIP Upload Issues

If you encounter "The module must be either a .zip file or a tarball" error:
- Ensure the ZIP contains the `membership/` folder, not files directly
- Check that the folder name is exactly `membership` (lowercase)
- Verify the ZIP is not corrupted

### Installation Fails

If installation fails:
- Check PHP error logs
- Verify database permissions
- Ensure PHP version meets requirements
- Check that no other module is using the same database table names

### Module Not Appearing

If the module doesn't appear after upload:
- Clear PrestaShop cache
- Check file permissions (755 for folders, 644 for files)
- Verify the main file `membership.php` exists and is readable

## Uninstallation

To uninstall the module:
1. Go to **Modules > Module Manager**
2. Find "Customer Membership"
3. Click the dropdown menu (⋮)
4. Select **"Uninstall"**
5. Confirm the action

**Note:** Uninstalling will remove all module data including customer memberships and product member prices.

## Support

For issues or questions:
- Check the README.md for module documentation
- Review PrestaShop logs: `/var/logs/`
- Verify module compatibility with your PrestaShop version

## License

This module is licensed under the Academic Free License (AFL 3.0).
