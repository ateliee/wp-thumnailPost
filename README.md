# 記事中のimgタグから自動でサムネイルを取得するWordpressプラグイン
## インストール方法
ダウンロードしたファイルをWordpressのプラグインディレクトリへ入れてください。
プラグインを有効化することでご利用頂けます。

## 使い方
テンプレートファイルにて指定する場合
```
<?php thumbnailPost($postId); ?>
```
また、ショートコードにも対応しています。
```
[thumbnailPost]
[thumbnailPost postid='1']
```