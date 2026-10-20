# NDC default logo

Official source: https://inecnigeria.org/parties/nigeria-democratic-congress

Source image: https://inecnigeria.org/_nuxt/nigeria-democratic-congress_logo.BRx6oQl5.jpeg

The source is converted to PNG without redesigning the logo by `php scripts/fetch-ndc-logo.php`.
The normalized image is committed at `public/assets/img/parties/NDC.png` and
`public/uploads/system_images/politicalparty/NDC-INEC-2026-10.png`.
Release packaging requires both files. Upgrades preserve uploads and copy the
bundled asset into the political party directory only if that default file is
absent. Existing NDC entries and custom logos are preserved.
