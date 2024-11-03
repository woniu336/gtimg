import { useEffect } from 'react';
import Head from 'next/head';

export default function Home() {
  useEffect(() => {
    // 将原来 index.html 中的 script 代码移到这里
  }, []);

  return (
    <>
      <Head>
        <title>腾讯Gtimg图床</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      </Head>
      {/* 将原来 index.html 中的 body 内容放在这里 */}
      <div className="container">
        <h2>腾讯Gtimg图床上传</h2>
        <div id="uploadArea">
          点击或拖拽图片到此处上传
          <input type="file" id="fileInput" accept="image/*" multiple />
        </div>
        <button id="uploadBtn">上传图片</button>
        <button id="copyAllLinksBtn">全部复制链接</button>
        <div className="progress-container">
          <div className="progress-bar"></div>
        </div>
        <div className="progress-text"></div>
        <div id="uploadStatus"></div>
        <div id="resultContainer"></div>
      </div>
      <style jsx>{`
        /* 将原来 index.html 中的 style 内容放在这里 */
      `}</style>
    </>
  );
} 