<?php

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Sandbox\SecurityError;
use Twig\Sandbox\SecurityNotAllowedTagError;
use Twig\Sandbox\SecurityNotAllowedFilterError;
use Twig\Sandbox\SecurityNotAllowedFunctionError;
use Twig\Source;
use Twig\Template;

/* home.twig */
class __TwigTemplate_b272e08846546df9f6a42f143d93ef54b70937990d36c16a5c2300a19057162d extends \Twig\Template
{
    private $source;
    private $macros = [];

    public function __construct(Environment $env)
    {
        parent::__construct($env);

        $this->source = $this->getSourceContext();

        $this->parent = false;

        $this->blocks = [
        ];
    }

    protected function doDisplay(array $context, array $blocks = [])
    {
        $macros = $this->macros;
        // line 1
        echo "<!DOCTYPE html>
<html>
    <head>
        <meta charset=\"utf-8\"/>
        <title>Slim 3</title>
        <link href='//fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>
        <link rel=\"stylesheet\" href=\"";
        // line 7
        echo twig_escape_filter($this->env, url("css/app.min.css"), "html", null, true);
        echo "\">
    </head>
    <body>
        <h1>Slim</h1>
        <div>a microframework for PHP</div>

        ";
        // line 13
        if (($context["name"] ?? null)) {
            // line 14
            echo "            <h2>Hello ";
            echo twig_escape_filter($this->env, ($context["name"] ?? null), "html", null, true);
            echo "!</h2>
        ";
        } else {
            // line 16
            echo "            <p>Try <a href=\"http://www.slimframework.com\">SlimFramework</a>
        ";
        }
        // line 18
        echo "
                <script src=\"";
        // line 19
        echo twig_escape_filter($this->env, url("js/app.js"), "html", null, true);
        echo "\"></script>

                <script id=\"__bs_script__\">//<![CDATA[
                    document.write(\"<script async src='http://localhost:3000/browser-sync/browser-sync-client.js?v=2.18.8'><\\/script>\".replace(\"HOST\", location.hostname));
                    //]]></script>
    </body>
</html>
";
    }

    public function getTemplateName()
    {
        return "home.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  69 => 19,  66 => 18,  62 => 16,  56 => 14,  54 => 13,  45 => 7,  37 => 1,);
    }

    public function getSourceContext()
    {
        return new Source("<!DOCTYPE html>
<html>
    <head>
        <meta charset=\"utf-8\"/>
        <title>Slim 3</title>
        <link href='//fonts.googleapis.com/css?family=Lato:300' rel='stylesheet' type='text/css'>
        <link rel=\"stylesheet\" href=\"{{ url('css/app.min.css') }}\">
    </head>
    <body>
        <h1>Slim</h1>
        <div>a microframework for PHP</div>

        {% if name %}
            <h2>Hello {{ name }}!</h2>
        {% else %}
            <p>Try <a href=\"http://www.slimframework.com\">SlimFramework</a>
        {% endif %}

                <script src=\"{{ url('js/app.js') }}\"></script>

                <script id=\"__bs_script__\">//<![CDATA[
                    document.write(\"<script async src='http://localhost:3000/browser-sync/browser-sync-client.js?v=2.18.8'><\\/script>\".replace(\"HOST\", location.hostname));
                    //]]></script>
    </body>
</html>
", "home.twig", "E:\\XAMPP\\htdocs\\works\\webapi\\resources\\views\\home.twig");
    }
}
