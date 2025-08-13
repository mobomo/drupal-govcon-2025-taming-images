# Drupal GovCon 2025
## From Mess to Masterpiece
**Taming Images in Drupal the Thoughtful Way**

### Setup <a name="setup"></a>

- Use [DDEV](https://ddev.readthedocs.io/en/stable/)
- `git clone https://github.com/mobomo/drupal-govcon-2025-taming-images.git`
- `ddev start`
- `ddev co i`
- `ddev robo project:init`
- `ddev dr uli` or use **admin**:*admin*


### Useful commands <a name="commands"></a>

- `ddev dr cex -y` - export configuration
- `ddev robo local:update` - update database, import configuration, build theme