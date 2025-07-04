#!/usr/bin/env python3
"""
Enhanced GenAI OpenVINO Handler for Advanced Forum Search and Summarization.

This module provides an improved AI handler using OpenVINO GenAI with advanced features
for better text generation, streaming responses, and forum-specific optimizations.
"""

import logging
from pathlib import Path
from typing import Any, Dict, List, Optional

from ..core.interfaces import AIHandler
from ..core.constants import POLISH_STOPWORDS, MAX_CONTEXT_LENGTH, MAX_SUMMARY_LENGTH, MAX_ANSWER_LENGTH
from ..utils.text_processing import normalize_polish_text

logger = logging.getLogger(__name__)

try:
    # OpenVINO GenAI imports with enhanced features
    import openvino_genai as ov_genai
    GENAI_AVAILABLE = True
    logger.info("OpenVINO GenAI available with advanced features")
except ImportError:
    logger.warning("OpenVINO GenAI not available")
    GENAI_AVAILABLE = False

try:
    # Fallback imports for embeddings
    from sentence_transformers import SentenceTransformer
    from sklearn.metrics.pairwise import cosine_similarity
    EMBEDDINGS_AVAILABLE = True
except ImportError:
    logger.warning("Embedding libraries not available")
    EMBEDDINGS_AVAILABLE = False

# Enhanced configuration constants
DEFAULT_CHAT_MODEL = "TinyLlama/TinyLlama-1.1B-Chat-v1.0"
DEFAULT_EMBEDDING_MODEL = "sentence-transformers/paraphrase-multilingual-mpnet-base-v2"

# Enhanced Polish prompts for forum-specific content
ENHANCED_POLISH_PROMPTS = {
    "summarize": """Streć poniższy wpis z forum noży w kontekście zapytania użytkownika. 
Skup się na praktycznych informacjach o nożach, opiniach, doświadczeniach i rekomendacjach.
Użyj maksymalnie 3-4 zdań w języku polskim.

Zapytanie: {query}
Treść: {content}

Streszczenie:""",
    
    "answer": """Odpowiedz na pytanie użytkownika na podstawie treści z forum o nożach.
Odpowiedz w języku polskim, w sposób zwięzły i praktyczny.
Skup się na faktach, opiniach i doświadczeniach związanych z nożami.

Pytanie: {query}
Kontekst z forum: {context}

Odpowiedź:""",
    
    "enhance_query": """Popraw i rozszerz zapytanie o noże, dodając synonimy i powiązane terminy.
Zwróć tylko poprawione zapytanie w języku polskim.

Oryginalne zapytanie: {query}

Poprawione zapytanie:""",
    
    "classify": """Klasyfikuj wpis z forum o nożach do jednej z kategorii:
- opinie_produkty: opinie o konkretnych nożach
- porady_techniczne: porady dotyczące używania, konserwacji
- rekomendacje: rekomendacje zakupu
- dyskusja_ogolna: ogólna dyskusja o nożach
- inne: pozostałe treści

Treść: {content}

Kategoria:"""
}


class GenAIHandler(AIHandler):
    """Enhanced OpenVINO GenAI handler for advanced text generation and forum optimization."""
    
    def __init__(self, model_path: Optional[str] = None, device: str = "CPU"):
        """Initialize GenAI handler.
        
        Args:
            model_path: Path to the GenAI model
            device: Target device for inference (CPU, GPU, etc.)
        """
        self.model_path = Path(model_path) if model_path else None
        self.device = device
        self.pipe = None
        self.embedding_model = None
        self.generation_config = None
        
        if not GENAI_AVAILABLE:
            logger.error("OpenVINO GenAI not available - handler will be limited")
            return
            
        self._setup_generation_config()
        
        try:
            if self.model_path and self.model_path.exists():
                self._load_model()
        except Exception as e:
            logger.error(f"Failed to initialize GenAI handler: {e}")
    
    def _setup_generation_config(self) -> None:
        """Setup generation configuration for OpenVINO GenAI."""
        if not GENAI_AVAILABLE:
            return
            
        try:
            self.generation_config = ov_genai.GenerationConfig()
            self.generation_config.max_new_tokens = MAX_ANSWER_LENGTH
            self.generation_config.temperature = 0.7
            self.generation_config.top_p = 0.9
            self.generation_config.do_sample = True
            self.generation_config.repetition_penalty = 1.1
        except Exception as e:
            logger.error(f"Failed to setup generation config: {e}")
    
    def _load_model(self) -> bool:
        """Load OpenVINO GenAI model.
        
        Returns:
            True if model loaded successfully, False otherwise
        """
        try:
            if not self.model_path or not self.model_path.exists():
                logger.error(f"Model path does not exist: {self.model_path}")
                return False
            
            self.pipe = ov_genai.LLMPipeline(str(self.model_path), self.device)
            logger.info(f"GenAI model loaded: {self.model_path}")
            return True
        except Exception as e:
            logger.error(f"Failed to load GenAI model: {e}")
            return False
    
    def _load_embedding_model(self) -> bool:
        """Load sentence transformer model for embeddings.
        
        Returns:
            True if model loaded successfully, False otherwise
        """
        if not EMBEDDINGS_AVAILABLE:
            logger.warning("Embedding libraries not available")
            return False
            
        try:
            self.embedding_model = SentenceTransformer(DEFAULT_EMBEDDING_MODEL)
            logger.info("Embedding model loaded successfully")
            return True
        except Exception as e:
            logger.error(f"Failed to load embedding model: {e}")
            return False
    
    def generate_text(self, prompt: str, max_tokens: Optional[int] = None) -> str:
        """Generate text using OpenVINO GenAI.
        
        Args:
            prompt: Input prompt for generation
            max_tokens: Maximum tokens to generate
            
        Returns:
            Generated text
        """
        if not self.pipe:
            if not self._load_model():
                return "Model not available"
        
        try:
            # Update generation config if max_tokens specified
            if max_tokens and self.generation_config:
                self.generation_config.max_new_tokens = max_tokens
            
            result = self.pipe.generate(prompt, self.generation_config)
            return result.strip()
        except Exception as e:
            logger.error(f"Failed to generate text: {e}")
            return f"Generation failed: {str(e)}"
    
    def summarize_content(self, content: str, query: str, max_length: int = MAX_SUMMARY_LENGTH) -> str:
        """Summarize forum content in Polish context.
        
        Args:
            content: Content to summarize
            query: User query for context
            max_length: Maximum summary length
            
        Returns:
            Summarized content in Polish
        """
        try:
            # Preprocess content and query
            clean_content = normalize_polish_text(content)
            clean_query = normalize_polish_text(query)
            
            # Truncate content if too long
            if len(clean_content) > MAX_CONTEXT_LENGTH:
                clean_content = clean_content[:MAX_CONTEXT_LENGTH] + "..."
            
            prompt = ENHANCED_POLISH_PROMPTS["summarize"].format(
                query=clean_query,
                content=clean_content
            )
            
            summary = self.generate_text(prompt, max_length)
            
            # Clean up and validate summary
            if not summary or "Generation failed" in summary:
                # Fallback to simple truncation
                sentences = clean_content.split('. ')
                summary = '. '.join(sentences[:2]) + '.'
            
            return summary[:max_length] if len(summary) > max_length else summary
        except Exception as e:
            logger.error(f"Failed to summarize content: {e}")
            return content[:max_length] + "..." if len(content) > max_length else content
    
    def answer_question(self, query: str, context: str, max_length: int = MAX_ANSWER_LENGTH) -> str:
        """Answer user question based on forum context.
        
        Args:
            query: User question
            context: Forum context for answering
            max_length: Maximum answer length
            
        Returns:
            Generated answer in Polish
        """
        try:
            clean_query = normalize_polish_text(query)
            clean_context = normalize_polish_text(context)
            
            # Truncate context if too long
            if len(clean_context) > MAX_CONTEXT_LENGTH:
                clean_context = clean_context[:MAX_CONTEXT_LENGTH] + "..."
            
            prompt = ENHANCED_POLISH_PROMPTS["answer"].format(
                query=clean_query,
                context=clean_context
            )
            
            answer = self.generate_text(prompt, max_length)
            
            # Validate answer
            if not answer or "Generation failed" in answer:
                return "Nie udało się wygenerować odpowiedzi na podstawie dostępnych informacji."
            
            return answer
        except Exception as e:
            logger.error(f"Failed to answer question: {e}")
            return "Wystąpił błąd podczas generowania odpowiedzi."
    
    def enhance_query(self, query: str) -> str:
        """Enhance search query with synonyms and related terms.
        
        Args:
            query: Original search query
            
        Returns:
            Enhanced query with additional terms
        """
        try:
            clean_query = normalize_polish_text(query)
            
            prompt = ENHANCED_POLISH_PROMPTS["enhance_query"].format(query=clean_query)
            enhanced = self.generate_text(prompt, 50)
            
            if enhanced and "Generation failed" not in enhanced:
                return enhanced.strip()
            
            return query  # Return original if enhancement fails
        except Exception as e:
            logger.error(f"Failed to enhance query: {e}")
            return query
    
    def classify_content(self, content: str) -> str:
        """Classify forum content into categories.
        
        Args:
            content: Content to classify
            
        Returns:
            Content category
        """
        try:
            clean_content = normalize_polish_text(content)
            
            # Truncate if too long
            if len(clean_content) > 500:
                clean_content = clean_content[:500] + "..."
            
            prompt = ENHANCED_POLISH_PROMPTS["classify"].format(content=clean_content)
            category = self.generate_text(prompt, 20)
            
            # Validate category
            valid_categories = ["opinie_produkty", "porady_techniczne", "rekomendacje", "dyskusja_ogolna", "inne"]
            category = category.strip().lower()
            
            if any(cat in category for cat in valid_categories):
                return category
            
            return "inne"  # Default category
        except Exception as e:
            logger.error(f"Failed to classify content: {e}")
            return "inne"
    
    def generate_embeddings(self, texts: List[str]) -> Optional[NDArray]:
        """Generate embeddings for texts using fallback model.
        
        Args:
            texts: List of texts to embed
            
        Returns:
            Embeddings array or None if failed
        """
        if not self.embedding_model and not self._load_embedding_model():
            return None
        
        try:
            # Preprocess texts
            processed_texts = []
            for text in texts:
                normalized = normalize_polish_text(text)
                without_stopwords = remove_stopwords(normalized, POLISH_STOPWORDS)
                processed_texts.append(without_stopwords)
            
            embeddings = self.embedding_model.encode(processed_texts)
            return embeddings
        except Exception as e:
            logger.error(f"Failed to generate embeddings: {e}")
            return None
    
    def process_query(self, query: str, context: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        """Process search query with GenAI enhancement.
        
        Args:
            query: Search query text
            context: Additional context for processing
            
        Returns:
            Dictionary with processed query and metadata
        """
        try:
            # Basic processing
            normalized_query = normalize_polish_text(query)
            clean_query = remove_stopwords(normalized_query, POLISH_STOPWORDS)
            
            # AI enhancements
            enhanced_query = self.enhance_query(query)
            
            # Generate embeddings if available
            query_embedding = None
            embeddings = self.generate_embeddings([clean_query])
            if embeddings is not None:
                query_embedding = embeddings[0]
            
            return {
                'original_query': query,
                'normalized_query': normalized_query,
                'clean_query': clean_query,
                'enhanced_query': enhanced_query,
                'embedding': query_embedding.tolist() if query_embedding is not None else None,
                'context': context or {}
            }
        except Exception as e:
            logger.error(f"Failed to process query: {e}")
            return {
                'original_query': query,
                'normalized_query': query,
                'clean_query': query,
                'enhanced_query': query,
                'embedding': None,
                'context': context or {}
            }
    
    def enhance_results(self, results: List[Dict[str, Any]], query: str) -> List[Dict[str, Any]]:
        """Enhance search results with AI-generated content.
        
        Args:
            results: List of search result dictionaries
            query: Original search query
            
        Returns:
            Enhanced results with AI summaries, answers, and classifications
        """
        try:
            enhanced_results = []
            
            for result in results:
                enhanced_result = result.copy()
                content = result.get('content', result.get('body', ''))
                
                if content:
                    # Generate AI summary
                    summary = self.summarize_content(content, query)
                    enhanced_result['ai_summary'] = summary
                    
                    # Classify content
                    category = self.classify_content(content)
                    enhanced_result['ai_category'] = category
                    
                    # Generate embeddings for similarity scoring
                    embeddings = self.generate_embeddings([content])
                    if embeddings is not None:
                        enhanced_result['embedding'] = embeddings[0].tolist()
                
                enhanced_results.append(enhanced_result)
            
            return enhanced_results
        except Exception as e:
            logger.error(f"Failed to enhance results: {e}")
            return results
    
    def is_available(self) -> bool:
        """Check if GenAI handler is available and functional.
        
        Returns:
            True if handler is available, False otherwise
        """
        return GENAI_AVAILABLE
    
    def get_status(self) -> Dict[str, Any]:
        """Get current status of the handler.
        
        Returns:
            Status dictionary with availability and model info
        """
        return {
            'available': self.is_available(),
            'genai_available': GENAI_AVAILABLE,
            'embeddings_available': EMBEDDINGS_AVAILABLE,
            'model_loaded': self.pipe is not None,
            'embedding_model_loaded': self.embedding_model is not None,
            'device': self.device,
            'model_path': str(self.model_path) if self.model_path else None
        }
