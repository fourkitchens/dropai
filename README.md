# DropAI Module

## Overview

The DropAI suite comprises modules that enhance Drupal websites with AI capabilities, facilitating the transformation of Drupal's data model into a Language Model (LLM). It facilitates the creation of embeddings from Drupal content and metadata, enabling efficient content management and transformation into a vector database. This is an evolution of the [ai_engine module](https://github.com/yalesites-org/ai_engine) which relied on exteranally hosted resources on the Azure Cloud.

## Objectives

This project aims to empower Drupal developers and site owners to seamlessly integrate AI tools into their workflows. Our primary focus is on simplifying the onboarding process, ensuring that any Drupal developer can initiate AI projects with a single command using familiar tools.

1. **Ease of Onboarding**: Our guiding principle is to make AI integration as straightforward as possible. The project is designed to allow developers to start using AI tools effortlessly.

2. **Local Development and Deployment**: Traditional Drupal applications are monolithic and often don't leverage cloud services effectively. To overcome this, we are focusing on using native PHP libraries and locally hosted Python tools, enabling services to move seamlessly through development stages—from local setups to multidevs, staging, and production.

3. **Service Modularity**: We are employing a strategy that allows easy swapping of various components such as embedding models, splitting strategies, storage formats, and sourcing mechanisms. This modular approach facilitates quick testing and frequent experimentation, enhancing the flexibility and adaptability of our AI tools.

4. **Local Vector Storage**: In alignment with our theme of locally hosted services, we are utilizing MongoDB Atlas as a local vector store. MongoDB's existing database interfaces for Drupal make it an ideal solution for local vector storage, eliminating the need to manage multiple cloud services for local testing.

## Modularity and experimentation through Plugins

Using plugins for an AI pipeline enhances modularity and flexibility by allowing each data transformation task, such as loading content, transforming data, or generating embeddings, to be handled independently. This approach enables easy customization and adaptation, as new methods or sources can be integrated seamlessly. Additionally, plugins promote reusability across different projects and scalability, allowing the system to grow and evolve without significant reengineering. Finally, the isolation of each plugin simplifies troubleshooting and enhances the overall robustness of the pipeline.

* **Loader plugins** extract content from a data source and transform it into a string format. The default loader renders content entities using their default or canonical display mode, ensuring that nodes and other rich content entities are properly rendered. Additional loaders are included to extract content from media and documents.

* **Preprocessor plugins** clean and format the loaded text, preparing it for ingestion by AI services. This module supports two preprocessors: plain text and markdown, ensuring compatibility with different text formats.

* **Tokenizer plugins** are essential for understanding the size of content to ensure it fits within model and usage limits. They are also useful for estimating billing and usage information.

* **Splitter plugins** break down content into smaller chunks to ensure content fits within system constraints and allows for efficient processing. Breaking content into logical segments can improve the accuracy of AI models, as they can focus on smaller, more coherent pieces of information.

* ***Embedding plugins** generate vectors from external services, enabling advanced AI functionalities such as semantic search and content analysis. These vectors represent the content in a numerical format that AI models can understand and work with.

* **Storage plugins** save embeddings and related metadata into a vector database. This can be an external SaaS solution or a local MongoDB Atlas database for a pure localhost solution.







