# Normalized Responsive Images
The **Normalized Responsive Images** recipe provides several sets of aspect ratio based image styles with normalized dimensions (see [Normalized Image Styles](https://www.drupal.org/project/normalized_image_styles)). For each aspect ratio set there is a corresponding _responsive image style_ and an _image view mode_ to make the best use of the sets throughout your site.

## Recipe Purpose
This recipe is designed to do the following:

- Install 20 sets of aspect ratio based image styles with normalized dimensions
- Install 20 responsive image styles, one for each aspect ratio
- Install 20 image view modes, one for each responsive image style

## Installing
- Start with a Drupal ^10.3 site.
- Install the 'Default' profile.
- Apply the recipe

The recipe can be applied via Drush ^13

To apply all 20 sets and a demo:

```shell
drush recipe recipes/normalized_responsive_images/normalized_responsive_images
```

To apply any individual set, such as "Landscape Anamorphic":

```shell
drush recipe recipes/normalized_responsive_images/nri_landscape_anamorphic
```

Or by using `ddev exec`

```shell
ddev exec -d /var/www/html/web php core/scripts/drupal recipe recipes/normalized_responsive_images/normalized_responsive_images
```

If all goes well, you should see the following output:

```shell
 [OK] Normalized Responsive Images applied successfully
```

Clear the cache after the recipe is applied. When going back to the site,
all the recipe configuration and customization has been applied.

## Usage
There are at least three ways to use Normalized Responsive Images
1. Use a rendered _image view mode_ when embedding image media in CKEditor
2. Use a "Responsive image" field formatter anywhere images are displayed
3. Use a single "Image" field formatter anywhere images are displayed