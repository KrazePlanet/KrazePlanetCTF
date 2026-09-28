# Fujitsu FM3V2 Player Documentation

## File Management System

This directory contains the Fujitsu FM3V2 Player file management system and attachment handler.

## File Structure

```
subdomains/auth/
├── index.php (Main file management interface)
├── README.md (This file)
└── pub/
    ├── index.html (Public files navigation)
    └── fujitsu/
        ├── index.html (Fujitsu library navigation)
        └── fm3v2/
            ├── index.html (FM3V2 navigation)
            └── player/
                ├── attach.html (Attachment handler)
                └── index.html (Player file manager)
```

## Navigation

1. **Main Interface**: Use the main `index.php` file for the Driftly file management interface
2. **Direct Access**: Navigate through the `pub/` directory structure for direct file access

## Attachment Handler

The attachment handler (`attach.html`) processes file attachments through URL parameters. Add the target file or URL after the `?` in the address bar.

## FM3V2 Player

The FM3V2 Player is a multimedia file player developed by Fujitsu for enterprise use.