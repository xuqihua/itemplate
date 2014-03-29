<?php namespace Xuqihua\Template;

use Illuminate\View\Compilers\Compiler;
use Illuminate\View\Compilers\CompilerInterface;

class TemplateCompiler extends Compiler implements CompilerInterface {

	/**
	 * All of the registered extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * The file currently being compiled.
	 *
	 * @var string
	 */
	protected $path;

	/**
	 * All of the available compiler functions.
	 *
	 * @var array
	 */
	protected $compilers = array(
		'Extensions',
	);

	/**
	 * Array of opening and closing tags for echos.
	 *
	 * @var array
	 */
	protected $contentTags = array('<!--{', '}-->');

	/**
	 * Array of opening and closing tags for escaped echos.
	 *
	 * @var array
	 */
	protected $escapedTags = array('{{{', '}}}');

	
	/**
	 * Get the path to the compiled version of a view.
	 *
	 * @param  string  $path
	 * @return string
	 */
	public function getCompiledPath($path)
	{
		return $this->cachePath.'/'.md5($path);
	}
	/**s
	 * Compile the view at the given path.
	 *
	 * @param  string  $path
	 * @return void
	 */
	public function compile($path = null)
	{
		if ($path)
		{
			$this->setPath($path);
		}

		$contents = $this->compileString($this->files->get($this->getPath()));

		if ( ! is_null($this->cachePath))
		{
			$this->files->put($this->getCompiledPath($this->getPath()), $contents);
		}
	}

	/**
	 * Get the path currently being compiled.
	 *
	 * @return string
	 */
	public function getPath()
	{
		return $this->path;
	}

	/**
	 * Set the path currently being compiled.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setPath($path)
	{
		$this->path = $path;
	}

	/**
	 * Compile the given HTML template contents.
	 *
	 * @param  string  $value
	 * @return string
	 */
	public function compileString($str)
	{
		$str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);
		$str = preg_replace ( "/\{template\s+(.+)\}/", "<?php echo View::make(\\1,array_except(get_defined_vars(), array('__data', '__path')))->render(); ?>", $str );
		$str = preg_replace ( "/\{include\s+(.+)\}/", "<?php include \\1; ?>", $str );
		$str = preg_replace ( "/\{lang\s+(.+)\}/", "<?php echo \Illuminate\Support\Facades\Lang::get\\1; ?>", $str );
		$str = preg_replace ( "/\{php\s+(.+)\}/", "<?php \\1?>", $str );
		$str = preg_replace ( "/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str );
		$str = preg_replace ( "/\{else\}/", "<?php } else { ?>", $str );
		$str = preg_replace ( "/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str );
		$str = preg_replace ( "/\{\/if\}/", "<?php } ?>", $str );
		$str = preg_replace("/\{for\s+(.+?)\}/","<?php for(\\1) { ?>",$str);
		$str = preg_replace("/\{\/for\}/","<?php } ?>",$str);
		$str = preg_replace("/\{\+\+(.+?)\}/","<?php ++\\1; ?>",$str);
		$str = preg_replace("/\{\-\-(.+?)\}/","<?php --\\1; ?>",$str);
		$str = preg_replace("/\{(.+?)\+\+\}/","<?php \\1++; ?>",$str);
		$str = preg_replace("/\{(.+?)\-\-\}/","<?php \\1--; ?>",$str);
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\}/", "<?php \$n=1;if(is_array(\\1)) foreach(\\1 AS \\2) { ?>", $str );
		$str = preg_replace ( "/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php \$n=1; if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str );
		$str = preg_replace ( "/\{\/loop\}/", "<?php \$n++;}unset(\$n); ?>", $str );
		$str = preg_replace ( "/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace ( "/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace ( "/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str );
		$str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", function($m){
			return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", '<?php echo '.$m[1].';?>'));
		},$str);
		$str = preg_replace ( "/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str );
		$str = preg_replace('/([\n\r]+)([\t\s]+)/s', "$1", $str);

		return $str;
	}

	/**
	 * Register a custom HTML compiler.
	 *
	 * @param  Closure  $compiler
	 * @return void
	 */
	public function extend(Closure $compiler)
	{
		$this->extensions[] = $compiler;
	}

	/**
	 * Execute the user defined extensions.
	 *
	 * @param  string  $value
	 * @return string
	 */
	protected function compileExtensions($value)
	{
		foreach ($this->extensions as $compiler)
		{
			$value = call_user_func($compiler, $value, $this);
		}

		return $value;
	}
}
