=== Twig That ===
Contributors: nerdsfarm, poglie, frapesce
Tags: twig, timber, editor, gutenberg, dynamic, template, engine, code
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 1.0
Requires PHP: 7.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Exploit the limitless potentials of Wordpress, create dynamic content on all your pages with the semplicity of Twig placeholders.

== Description ==

= Mission =

This plugin makes it possible to use the Twig template engine directly on your page content.
Twig is a beloved meta language supported by most CMS (such as Drupal, Prestashop, etc.), now you can use it on WordPress in a powerful and simple way, without the need to write file-based theme templates.
After the introduction of Live Drag & Drop Editors to create all Templates directly from the admin area, like Gutenberg, this plugin will solve the lack of generating something more dynamic with no extra time, avoiding also the creation of shortcodes.

= Timber Library =

**Twig + Timber = The perfect match for WordPress**
The Twig Engine was created for Symfony project, but there it could be integrated also in different projects like WordPress. 
Timber developed this perfect integration, so the plugin take the most from this incredible library.
Compatible also with **Timber Framework** plugin.
See [official TIMBER documentation](https://timber.github.io/docs/).

= Some extra documentation and examples =

**All variables available by default**
- post, current Post
- user, current logged in User
- author, current post Author
- term, current Term in archive page
- system, all server information
- site, all site Options
- theme, current active Theme informations
- menu, all Menu managed by WP
- product, current Product if WooCommerce is active
- wp_query (or posts), the main page query

Example:
{{post.post_title}}
{{post.my_custom_field}}
{{post.published_at|date("m/d/Y")}}
{{user.user_email}}
{{user.first_name}}
{{term.count}}
{{site.name}}
{{site.url}}
{{site.description}}
{{theme.link}}

**All PHP and native Wordpress functions**
You don't have to re-write all theme/plugin functions!
For advanced user, you can take advantage of all functions provided by WP.

Example:
{{ function('edit_post_link', 'Edit', '', '') }}
{{ function('wp_head') }}
{{ function('wp_footer') }}

**Quick Filters** 
Twig offers a variety of filters to transform text and other information into the desired output.

Example:
{{'now'|date('Y-m-d')}}
{{post.post_title|trim|lower}}
{{post.get_field('custom_link')|e('esc_url')}}
{{post.post_content|excerpt(30)}}
{{post.my_custom_field|var_dump}}

**Loops and Conditions**
All cycles (for) and structured code (if) are supported

{% if user.roles.administrator %}I am an admin{% endif %}

{% for post_id in post.related_posts_ids %}{{ function('get_the_title', post_id) }}{% endfor %}

{% for image in post.meta('gallery') %}{{ get_image(image) }}{% endfor %}

{% if post.thumbnail %}{{ post.thumbnail.src }}{% endif %}

{% for term in post.terms({query:{taxonomy:'category'} }) %}
  <a href="{{ term.link }}">{{ term.name }}</a>{% if not loop.last %}, {% endif %}
{% endfor %}



= Compatibility =
In Editor page you will see the original inserted Twig source code, everything will be dynamically translated in frontend.
Most used Wordpress Editors are actually supported:
- Gutenberg
- Classic Editor
- wpBackery
- Elementor
- Divi
- more will coming soon (Oxygen, Bricks, etc)

Contact us to increase the supported technologies.

== Installation ==

= Minimum Requirements =

* WordPress 5.0 or greater
* PHP version 7.1 or greater
* MySQL version 5.0 or greater
* WordPress Memory limit of 128 MB

= We recommend your host supports: =

* PHP version 8.0 or greater
* MySQL version 8.0 or greater
* WordPress Memory limit of 256 MB or higher is preferred

== Frequently Asked Questions ==

= Is compatible with Classic Editor? =

Yes, the Twig code will be rendered when used in any WYSIWYG editor.

= Is compatible with Gutenberg? =

Yes, you can use Twig in ALL native Blocks like "Headings" and "HTML".

= Is compatible with WPBakery Page Builder? =

Yes, you can use Twig in native Elements like "Text Block" and "Custom Heading".

= Is compatible with Divi? =

Yes, you can use Twig in native Modules like "Text" and "Code".

= Is compatible with Elementor? =

Yes, you can use Twig in native Widgets like "Text Editor".

= I write some Twig code which break the page, how can I solve? =

Simply turn off TwigThat plugin, resolve or delete the code issue in page and then reactivate plugin.

== Changelog ==

= 1.0 - 23-05-2022 =
* First release