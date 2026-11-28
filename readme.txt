=== Cardoza AI-Powered Hybrid Search ===
Contributors: vinojcardoza
Tags: search, semantic search, ai, embeddings, vector search
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Hybrid keyword + semantic search with optional AI answers. Uses only MySQL, so it runs on shared hosting — no vector database needed.

== Description ==

Cardoza Hybrid Search makes your site search understand meaning, not just matching words. A visitor searching for "how do I speed up my site" finds your post titled "Caching and performance tips" even though it never uses the word "speed".

Post content is split into chunks, turned into vector embeddings by an embedding service you choose, and stored in your existing MySQL database as compact INT8 vectors next to a FULLTEXT keyword index. Every search combines keyword and semantic ranking. No vector database, no extra server software — it works on ordinary shared hosting.

= Features =

* **Hybrid search** — MySQL FULLTEXT keyword search and vector similarity, combined with weighted Reciprocal Rank Fusion.
* **Replaces your theme's search** — the search box and results page keep working with your theme's templates and pagination; excerpts show the passage that matched. Can be switched off.
* **AI answers (optional)** — a chat model of your choice writes a short answer from the top results, with numbered citations linked to the source posts. Add it automatically above search results or with the `[cardoza_hs_answer]` shortcode.
* **Bring your own provider** — any OpenAI-compatible embeddings and chat completions API: OpenAI, OpenRouter, or a self-hosted Ollama or LM Studio server.
* **Choose what is indexed** — pick the post types (posts and pages by default, plus products, events or any public post type), and exclude single posts from the editor sidebar.
* **Automatic indexing** — posts are indexed in the background when saved; unchanged content is skipped, and trashed, deleted, unpublished and password-protected posts are removed.
* **Re-index tool** — re-index the whole site in small, resumable batches with a progress view and index statistics.
* **Fast and light** — 384-dimension vectors take 384 bytes each (about 11 MB for 30,000 chunks); results are cached for 5 minutes.
* **REST API** — public `/cardoza-hs/v1/search` and `/cardoza-hs/v1/answer` endpoints with per-visitor rate limits.
* **Graceful fallback** — if the embedding service is unreachable, search falls back to keyword results.

= Requirements =

* An OpenAI-compatible embeddings API reachable from your server that returns **384-dimension** vectors (for example `all-MiniLM-L6-v2`).
* For AI answers: an OpenAI-compatible chat completions API.

== External services ==

This plugin does not contact any service until you configure one. It connects only to the endpoint URLs you enter in its settings. You choose the provider, and that provider's terms of service and privacy policy apply.

= Embedding service =

Used to turn content and search queries into vectors.

* **What is sent:** the title and text content of published posts of the enabled post types (when they are saved or re-indexed), and the search terms visitors enter (when they search). The model name and, if you entered one, your API key are sent with each request.
* **When:** when a post is indexed and on every search that isn't already cached.
* **Where:** the embedding endpoint URL set on **Hybrid Search → Settings**.

= Chat service (optional) =

Used only if you configure it, to write AI answers.

* **What is sent:** the visitor's search terms and the title, URL and matching text of the top five search results, inside the prompt you configure. The model name and, if set, your API key are sent with each request.
* **When:** when an AI answer is displayed on a search results page or requested from the `/answer` REST endpoint, unless the answer is cached.
* **Where:** the chat endpoint URL set on **Hybrid Search → Chat**.

If you use a hosted provider, see its terms and privacy policy. For example:

* OpenAI — [Terms of use](https://openai.com/policies/terms-of-use), [Privacy policy](https://openai.com/policies/privacy-policy)
* OpenRouter — [Terms of service](https://openrouter.ai/terms), [Privacy policy](https://openrouter.ai/privacy)

A self-hosted server (such as Ollama or LM Studio) keeps all data on infrastructure you control.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/cardoza-hybrid-search`, or install it from **Plugins → Add New**.
2. Activate it from the **Plugins** screen. This creates the index tables.
3. Go to **Hybrid Search → Settings** and enter your embedding endpoint URL, API key (if required) and model name. To try it locally for free, see "Local testing with Ollama" below.
4. Go to **Hybrid Search → Re-Index** and click **Re-index all posts** to index existing content. New and updated posts are then indexed automatically.
5. Optional: configure a chat model on **Hybrid Search → Chat** and switch on **Show an AI answer above search results** on **Hybrid Search → Search**.

= Local testing with Ollama =

[Ollama](https://ollama.com) runs both the embedding model and the chat model on your own computer, with no API key and no data leaving your machine.

1. Install Ollama from [ollama.com/download](https://ollama.com/download) and start it (open the app, or run `ollama serve`). It listens on `http://127.0.0.1:11434`.
2. Pull the models: `ollama pull all-minilm` (embeddings, 384 dimensions, about 46 MB) and `ollama pull qwen3:4b` (chat, about 2.5 GB; any chat model works). The embedding model must produce 384-dimension vectors, so models such as `nomic-embed-text` (768) are not supported yet.
3. Choose the base URL: `http://127.0.0.1:11434` if WordPress runs directly on your computer (Local, MAMP, `wp server`), or `http://host.docker.internal:11434` if WordPress runs in Docker.
4. On **Hybrid Search → Settings**, set Endpoint URL to `<base URL>/v1/embeddings`, leave API key empty, and set Model to `all-minilm`.
5. On **Hybrid Search → Chat**, set Endpoint URL to `<base URL>/v1/chat/completions`, leave API key empty, and set Model to `qwen3:4b` (or the model you pulled).
6. Activate the plugin if you haven't, then click **Re-index all posts** on **Hybrid Search → Re-Index** and keep the page open until it finishes.
7. Switch on **Show an AI answer above search results** on **Hybrid Search → Search** and search your site.

For Docker on Linux, slow answers and WP-Cron in containers, see "How do I test it locally with Ollama?" in the FAQ.

== Frequently Asked Questions ==

= Do I need a vector database or special hosting? =

No. Vectors are stored in two tables in your existing MySQL database and compared in PHP. It is designed for sites of up to about 10,000 posts on shared hosting.

= Which embedding providers work? =

Any service using the OpenAI-compatible embeddings format (`POST {"model": "...", "input": [...]}`), including OpenAI, OpenRouter, Ollama and LM Studio. The model must currently produce 384-dimension vectors.

= How do I test it locally with Ollama? =

[Ollama](https://ollama.com) runs both the embedding model and the chat model on your own computer, with no API key and no data leaving your machine. Its OpenAI-compatible API works with the plugin unchanged.

1. Install Ollama from [ollama.com/download](https://ollama.com/download) and start it (open the app, or run `ollama serve`). It listens on `http://127.0.0.1:11434`.
2. Pull an embedding model and a chat model: `ollama pull all-minilm` (all-MiniLM-L6-v2, 384 dimensions, about 46 MB) and `ollama pull qwen3:4b` (about 2.5 GB; any chat model works). The embedding model must produce 384-dimension vectors, so models such as `nomic-embed-text` (768) are not supported yet.
3. Check that it answers: `curl http://127.0.0.1:11434/v1/embeddings -d '{"model": "all-minilm", "input": ["hello world"]}'`
4. Choose the base URL: `http://127.0.0.1:11434` if WordPress runs directly on your computer (Local, MAMP, `wp server`), or `http://host.docker.internal:11434` if WordPress runs in Docker.
5. On **Hybrid Search → Settings**, set Endpoint URL to `<base URL>/v1/embeddings`, leave API key empty, and set Model to `all-minilm` (Ollama requires the model name).
6. On **Hybrid Search → Chat**, set Endpoint URL to `<base URL>/v1/chat/completions`, leave API key empty, and set Model to `qwen3:4b` (or the model you pulled).
7. Click **Re-index all posts** on **Hybrid Search → Re-Index** and keep the page open until it finishes. Then switch on **Show an AI answer above search results** on **Hybrid Search → Search** and search your site.

Tips:

* **Docker on Linux:** add `extra_hosts: ["host.docker.internal:host-gateway"]` to the WordPress service and start Ollama with `OLLAMA_HOST=0.0.0.0 ollama serve` so containers can reach it. Docker Desktop on macOS and Windows works with the defaults.
* **Indexing on save in Docker:** WordPress often can't reach its own URL from inside a container, so WP-Cron doesn't run and saved posts aren't indexed automatically. Use the Re-Index page, or request `wp-cron.php` from your computer (for example `curl http://localhost:8080/wp-cron.php`, using your site's port).
* **Speed:** on a CPU the first request loads the model, and an answer can take tens of seconds (the plugin waits up to 120 seconds). Smaller models such as `qwen3:1.7b` or `llama3.2:3b` are faster. Reasoning output in `<think>` tags is removed from answers.
* **Errors** are written to the PHP error log (`wp-content/debug.log` with `WP_DEBUG_LOG` enabled).

= Does it send my content to a third party? =

Only to the endpoints you configure. See **External services** above. Using a self-hosted embedding and chat server keeps everything on your own infrastructure.

= What happens if the embedding service is down? =

Searches fall back to keyword-only results, and the error is written to the PHP error log. Posts that fail to index keep their previous index entry.

= I changed the embedding model. Do I need to re-index? =

Yes. Vectors from different models can't be compared, so run **Re-index all posts** after changing the model.

= New posts are not being indexed. =

Indexing runs in the background through WP-Cron. If your site disables WP-Cron or it can't reach itself, set up a server cron job that requests `wp-cron.php`. The Re-Index page always works, because it runs batches while it is open.

= How do I keep a post out of search? =

Open the post and switch on **Exclude from AI search** in the **Cardoza Hybrid Search** panel of the editor sidebar (or the meta box in the classic editor), then save. It is removed from the index immediately.

= How do I add the AI answer to a block theme? =

Switch on the automatic answer on **Hybrid Search → Search**, or add a Shortcode block containing `[cardoza_hs_answer]` to your theme's Search Results template.

= Can I turn off the search takeover and use only the REST API? =

Yes. Switch off **Use Cardoza Hybrid Search for site search** on **Hybrid Search → Search**. The REST endpoints keep working: `GET /wp-json/cardoza-hs/v1/search?q=...` and `POST /wp-json/cardoza-hs/v1/answer`.

= What is removed when I delete the plugin? =

Deleting the plugin from the Plugins screen drops its tables and removes its options, post meta, cached results and scheduled events. Deactivating it keeps everything.

== Screenshots ==

1. Search settings: site search takeover and the automatic AI answer.
2. Embedding service settings.
3. Chat model settings with the live prompt preview.
4. Post types to index.
5. Re-index progress and index statistics.
6. An AI answer with linked sources above the search results.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
